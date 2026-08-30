<?php

namespace App\Http\Controllers\SuperAdmin\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePartner;
use App\Models\AffiliatePayout;
use App\Services\Affiliate\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function __construct(private PayoutService $payouts)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $candidates = AffiliateCommission::query()
            ->join('affiliate_partners as ap', 'ap.id', '=', 'affiliate_commissions.partner_id')
            ->where('affiliate_commissions.status', 'approved')
            ->whereNull('affiliate_commissions.payout_id')
            ->selectRaw('affiliate_commissions.partner_id, ap.partner_code, ap.name,
                affiliate_commissions.period_year, affiliate_commissions.period_month,
                SUM(affiliate_commissions.commission_amount) as amount,
                COUNT(*) as line_count')
            ->groupBy('affiliate_commissions.partner_id', 'ap.partner_code', 'ap.name',
                'affiliate_commissions.period_year', 'affiliate_commissions.period_month')
            ->havingRaw('SUM(affiliate_commissions.commission_amount) <> 0')
            ->orderByDesc('affiliate_commissions.period_year')
            ->orderByDesc('affiliate_commissions.period_month')
            ->get();

        return view('superadmin.affiliate.payouts', [
            'payouts' => AffiliatePayout::with('partner:id,partner_code,name')
                ->when(in_array($status, ['draft', 'finalized', 'submitted', 'paid', 'void'], true),
                    fn ($q) => $q->where('status', $status))
                ->latest()->paginate(30)->withQueryString(),
            'candidates' => $candidates,
            'status' => $status,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['required', 'exists:affiliate_partners,id'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
            'skip_minimum' => ['nullable', 'boolean'],
        ]);

        $result = $this->payouts->finalize(
            $data['partner_id'], $data['year'], $data['month'], null,
            ! ($data['skip_minimum'] ?? false)
        );

        if ($result['ok']) {
            return redirect()->route('superadmin.affiliate.payouts.show', $result['payout'])
                ->with('status', 'Statement generated.');
        }

        return redirect()->route('superadmin.affiliate.payouts.index')->with('status', $result['error']);
    }

    public function show(AffiliatePayout $payout): View
    {
        $payout->load(['items', 'partner']);

        return view('superadmin.affiliate.payout-show', ['payout' => $payout]);
    }

    public function update(Request $request, AffiliatePayout $payout): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'mark_paid') {
            $this->payouts->markPaid($payout, $request->input('reference'));

            return redirect()->route('superadmin.affiliate.payouts.show', $payout)
                ->with('status', 'Statement marked as paid.');
        }

        if ($action === 'void') {
            $result = $this->payouts->void($payout);

            return redirect()->route('superadmin.affiliate.payouts.index')
                ->with('status', $result['ok'] ? 'Statement voided; its commissions are back in the pool.' : $result['error']);
        }

        return back();
    }
}
