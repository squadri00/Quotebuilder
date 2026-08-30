<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\AffiliatePayout;
use App\Services\Affiliate\AffiliateSettings;
use App\Services\Affiliate\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StatementController extends PortalController
{
    public function __construct(private PayoutService $payouts)
    {
    }

    public function index(Request $request): View
    {
        $partner = $this->partner();
        $ccy = AffiliateSettings::payoutCurrency();
        $year = $request->integer('y');
        $month = $request->integer('m');

        return view('affiliate.portal.statements', [
            'partner' => $partner,
            'currency' => $ccy,
            'minPayout' => AffiliateSettings::minPayout(),
            'eligible' => $this->payouts->eligibleMonths($partner->id, $ccy),
            'payouts' => AffiliatePayout::where('partner_id', $partner->id)
                ->orderByDesc('period_year')->orderByDesc('period_month')->get(),
            'previewYear' => $year ?: null,
            'previewMonth' => $month ?: null,
            'previewRows' => ($year && $month)
                ? $this->payouts->previewRows($partner->id, $year, $month, $ccy)
                : collect(),
        ]);
    }

    public function finalize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'y' => ['required', 'integer'],
            'm' => ['required', 'integer', 'between:1,12'],
        ]);

        $result = $this->payouts->finalize($this->partner()->id, $data['y'], $data['m']);

        if ($result['ok']) {
            return redirect()->route('affiliate.portal.statements.show', $result['payout'])
                ->with('status', 'Statement finalized.');
        }

        return redirect()->route('affiliate.portal.statements', ['y' => $data['y'], 'm' => $data['m']])
            ->with('status', $result['error']);
    }

    public function submit(AffiliatePayout $payout): RedirectResponse
    {
        abort_unless($payout->partner_id === $this->partner()->id, 403);
        $this->payouts->markSubmitted($payout);

        return redirect()->route('affiliate.portal.statements.show', $payout)
            ->with('status', 'Marked as submitted.');
    }

    public function show(AffiliatePayout $payout): View
    {
        abort_unless($payout->partner_id === $this->partner()->id, 403);
        $payout->load('items');

        return view('affiliate.portal.statement-show', ['payout' => $payout]);
    }

    public function txt(AffiliatePayout $payout): Response
    {
        abort_unless($payout->partner_id === $this->partner()->id, 403);
        abort_if($payout->status === 'draft', 404);

        $payout->increment('download_count');
        $payout->forceFill(['last_downloaded_at' => now()])->save();
        $payout->load('items');

        $L = [];
        $L[] = $payout->company_name_snapshot;
        $L[] = $payout->company_address_snapshot;
        $L[] = $payout->company_email_snapshot;
        $L[] = str_repeat('=', 70);
        $L[] = 'COMMISSION STATEMENT  ' . $payout->payout_number;
        $L[] = 'Period    : ' . $payout->periodLabel();
        $L[] = 'Currency  : ' . $payout->currency;
        $L[] = 'Due date  : ' . $payout->due_date->toDateString();
        $L[] = 'Status    : ' . ucfirst($payout->status);
        $L[] = str_repeat('-', 70);
        $L[] = 'FROM: ' . $payout->partner_name_snapshot . '  (' . $payout->partner->partner_code . ')';
        $L[] = '      ' . $payout->partner_email_snapshot;
        $L[] = str_repeat('-', 70);
        foreach ($payout->items as $it) {
            $L[] = sprintf(
                '%02d/%04d  %-26s  %10s  %6s  %12s',
                $it->period_month, $it->period_year,
                mb_substr((string) $it->business_name_snapshot, 0, 26),
                number_format((float) $it->base_amount, 2),
                $it->kind === 'commission' ? number_format((float) $it->rate, 2) . '%' : '-',
                number_format((float) $it->commission_amount, 2)
            );
        }
        $L[] = str_repeat('-', 70);
        $L[] = 'TOTAL DUE: ' . number_format((float) $payout->amount, 2) . ' ' . $payout->currency;

        return response(implode("\r\n", $L), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $payout->payout_number . '.txt"',
        ]);
    }
}
