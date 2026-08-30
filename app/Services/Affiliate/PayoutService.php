<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateCommission;
use App\Models\AffiliatePartner;
use App\Models\AffiliatePayout;
use App\Models\PlatformSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    /** Approved, not-yet-paid-out months for a partner (newest first). */
    public function eligibleMonths(int $partnerId, ?string $currency = null)
    {
        $currency ??= AffiliateSettings::payoutCurrency();

        return AffiliateCommission::query()
            ->selectRaw('period_year, period_month, COUNT(*) as line_count,
                         SUM(base_amount) as base_total, SUM(commission_amount) as amount_total')
            ->where('partner_id', $partnerId)
            ->where('currency', $currency)
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->groupBy('period_year', 'period_month')
            ->havingRaw('SUM(commission_amount) <> 0')
            ->orderByDesc('period_year')->orderByDesc('period_month')
            ->get();
    }

    public function previewRows(int $partnerId, int $year, int $month, ?string $currency = null)
    {
        $currency ??= AffiliateSettings::payoutCurrency();

        return AffiliateCommission::with('business:id,name')
            ->where('partner_id', $partnerId)
            ->where('currency', $currency)
            ->where('period_year', $year)->where('period_month', $month)
            ->where('status', 'approved')->whereNull('payout_id')
            ->orderBy('created_at')->orderBy('id')
            ->get();
    }

    public function generateNumber(string $partnerCode, int $year, int $month): string
    {
        $prefix = sprintf('AFP-%04d%02d-%s-', $year, $month,
            strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $partnerCode)));

        $last = AffiliatePayout::where('payout_number', 'like', $prefix . '%')
            ->orderByDesc('id')->value('payout_number');

        $n = ($last && preg_match('/-(\d+)$/', $last, $m)) ? ((int) $m[1] + 1) : 1;

        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{ok:bool, payout?:AffiliatePayout, error?:string}
     */
    public function finalize(int $partnerId, int $year, int $month, ?string $currency = null, bool $enforceMinimum = true): array
    {
        $currency ??= AffiliateSettings::payoutCurrency();
        $partner = AffiliatePartner::find($partnerId);
        if (! $partner) {
            return ['ok' => false, 'error' => 'Partner not found.'];
        }

        try {
            return DB::transaction(function () use ($partner, $partnerId, $year, $month, $currency, $enforceMinimum) {
                $dupe = AffiliatePayout::where('partner_id', $partnerId)
                    ->where('period_year', $year)->where('period_month', $month)
                    ->where('currency', $currency)->where('status', '!=', 'void')
                    ->lockForUpdate()->first();
                if ($dupe) {
                    return ['ok' => false, 'error' => 'A statement for that month already exists.'];
                }

                $lines = AffiliateCommission::where('partner_id', $partnerId)
                    ->where('currency', $currency)
                    ->where('period_year', $year)->where('period_month', $month)
                    ->where('status', 'approved')->whereNull('payout_id')
                    ->lockForUpdate()->get();

                if ($lines->isEmpty()) {
                    return ['ok' => false, 'error' => 'No approved commission for that month.'];
                }

                $amount = round($lines->sum('commission_amount'), 2);
                $baseTotal = round($lines->sum('base_amount'), 2);

                if ($enforceMinimum && $amount < AffiliateSettings::minPayout()) {
                    return ['ok' => false, 'error' => 'Below the minimum payout of '
                        . number_format(AffiliateSettings::minPayout(), 2) . ' ' . $currency . '.'];
                }

                $start = Carbon::create($year, $month, 1)->startOfDay();
                $company = PlatformSetting::get();

                $payout = AffiliatePayout::create([
                    'payout_number' => $this->generateNumber($partner->partner_code, $year, $month),
                    'partner_id' => $partnerId,
                    'period_year' => $year,
                    'period_month' => $month,
                    'period_start' => $start->toDateString(),
                    'period_end' => $start->copy()->endOfMonth()->toDateString(),
                    'due_date' => $start->copy()->addMonthNoOverflow()->day(15)->toDateString(),
                    'currency' => $currency,
                    'commission_count' => $lines->count(),
                    'base_total' => $baseTotal,
                    'rate_snapshot' => optional($lines->firstWhere('kind', 'commission'))->rate,
                    'amount' => $amount,
                    'status' => 'finalized',
                    'finalized_at' => now(),
                    'partner_name_snapshot' => $partner->company_name ?: $partner->name,
                    'partner_email_snapshot' => $partner->email,
                    'partner_address_snapshot' => $partner->address,
                    'partner_citystate_snapshot' => trim(implode(', ', array_filter([
                        $partner->city, $partner->state_province, $partner->postal_code,
                    ]))),
                    'partner_country_snapshot' => $partner->country,
                    'partner_tax_id_snapshot' => $partner->tax_id,
                    'company_name_snapshot' => $company->platform_name ?: config('app.name'),
                    'company_address_snapshot' => method_exists($company, 'formattedAddress')
                        ? $company->formattedAddress() : trim(implode(', ', array_filter([
                            $company->address_line1, $company->city, $company->state_province,
                            $company->postal_code, $company->country,
                        ]))),
                    'company_email_snapshot' => $company->contact_email,
                ]);

                AffiliateCommission::whereIn('id', $lines->pluck('id'))
                    ->update(['payout_id' => $payout->id, 'status' => 'on_payout']);

                return ['ok' => true, 'payout' => $payout->fresh()];
            });
        } catch (\Throwable $e) {
            Log::error('Affiliate payout finalize failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not finalize the statement. Please try again.'];
        }
    }

    public function markPaid(AffiliatePayout $payout, ?string $reference = null): bool
    {
        if (! in_array($payout->status, ['finalized', 'submitted'], true)) {
            return false;
        }

        return DB::transaction(function () use ($payout, $reference) {
            $payout->update(['status' => 'paid', 'paid_at' => now(), 'payment_reference' => $reference]);
            AffiliateCommission::where('payout_id', $payout->id)->where('status', 'on_payout')
                ->update(['status' => 'paid']);
            return true;
        });
    }

    public function markSubmitted(AffiliatePayout $payout): bool
    {
        if ($payout->status !== 'finalized') {
            return false;
        }
        return $payout->update(['status' => 'submitted', 'submitted_at' => now()]);
    }

    /**
     * @return array{ok:bool, error?:string}
     */
    public function void(AffiliatePayout $payout): array
    {
        if ($payout->status === 'paid') {
            return ['ok' => false, 'error' => 'Paid statements cannot be voided.'];
        }

        DB::transaction(function () use ($payout) {
            AffiliateCommission::where('payout_id', $payout->id)->where('status', 'on_payout')
                ->update(['payout_id' => null, 'status' => 'approved']);
            $payout->update(['status' => 'void']);
        });

        return ['ok' => true];
    }
}
