<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\Business;
use App\Models\TaxCollection;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    /**
     * Accrue the commission for one paid platform invoice (a
     * tax_collections row). Idempotent on source_tax_collection_id.
     * Returns the new commission, or null when nothing applies.
     */
    public function accrueForTaxCollection(TaxCollection $tc): ?AffiliateCommission
    {
        if (AffiliateCommission::where('source_tax_collection_id', $tc->id)->exists()) {
            return null;
        }

        $referral = AffiliateReferral::with('partner')
            ->where('business_id', $tc->business_id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if (! $referral) {
            return null;
        }

        $payoutCcy = AffiliateSettings::payoutCurrency();
        if (strtoupper($tc->currency) !== $payoutCcy) {
            Log::warning("Affiliate: tax_collection #{$tc->id} is {$tc->currency} but payouts are {$payoutCcy} — commission skipped.");
            return null;
        }

        $base = round((float) $tc->base_amount, 2);
        if ($base <= 0) {
            $base = round((float) $tc->total_amount, 2);
        }
        if ($base <= 0) {
            return null;
        }

        $rate = $referral->effectiveRate();
        $when = $tc->collected_at ?? $tc->created_at ?? now();

        $commission = AffiliateCommission::create([
            'referral_id' => $referral->id,
            'partner_id' => $referral->partner_id,
            'business_id' => $tc->business_id,
            'source_tax_collection_id' => $tc->id,
            'business_name_snapshot' => (string) (Business::whereKey($tc->business_id)->value('name') ?? ''),
            'period_year' => (int) $when->format('Y'),
            'period_month' => (int) $when->format('n'),
            'base_amount' => $base,
            'currency' => $payoutCcy,
            'rate' => $rate,
            'commission_amount' => round($base * $rate / 100, 2),
            'kind' => 'commission',
            'status' => $referral->status === 'approved' ? 'approved' : 'pending',
        ]);

        if ($referral->first_commission_at === null) {
            $referral->update(['first_commission_at' => now()]);
        }

        return $commission;
    }

    /** Back-fill every not-yet-accrued paid invoice for a business. */
    public function accrueForBusiness(int $businessId): int
    {
        if (! AffiliateSettings::enabled()) {
            return 0;
        }

        $hasReferral = AffiliateReferral::where('business_id', $businessId)
            ->whereIn('status', ['pending', 'approved'])->exists();
        if (! $hasReferral) {
            return 0;
        }

        $count = 0;
        TaxCollection::where('business_id', $businessId)
            ->whereNotIn('id', AffiliateCommission::query()
                ->whereNotNull('source_tax_collection_id')
                ->pluck('source_tax_collection_id'))
            ->orderBy('id')
            ->each(function (TaxCollection $tc) use (&$count) {
                if ($this->accrueForTaxCollection($tc)) {
                    $count++;
                }
            });

        return $count;
    }

    /** Nightly reconcile: expire stale claims + back-fill missed accruals. */
    public function reconcileAll(): array
    {
        $expired = app(ProspectService::class)->expireStale();

        $accrued = 0;
        AffiliateReferral::whereIn('status', ['pending', 'approved'])
            ->distinct()->pluck('business_id')
            ->each(function ($businessId) use (&$accrued) {
                $accrued += $this->accrueForBusiness((int) $businessId);
            });

        return ['claims_expired' => $expired, 'commissions_accrued' => $accrued];
    }

    public function approveReferral(AffiliateReferral $referral, ?int $adminId = null): void
    {
        $referral->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $adminId,
        ]);

        AffiliateCommission::where('referral_id', $referral->id)
            ->where('status', 'pending')
            ->whereNull('payout_id')
            ->update(['status' => 'approved']);
    }

    public function rejectReferral(AffiliateReferral $referral, ?string $reason = null): void
    {
        $referral->update([
            'status' => 'rejected',
            'ended_at' => now(),
            'ended_reason' => $reason ?: 'Rejected by Super Admin',
        ]);

        AffiliateCommission::where('referral_id', $referral->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('payout_id')
            ->update(['status' => 'void']);
    }

    /** Manual +/- line against a referral. */
    public function addAdjustment(AffiliateReferral $referral, float $amount, ?string $note, ?int $adminId, string $kind = 'adjustment'): AffiliateCommission
    {
        if ($kind === 'clawback' && $amount > 0) {
            $amount = -$amount;
        }

        return AffiliateCommission::create([
            'referral_id' => $referral->id,
            'partner_id' => $referral->partner_id,
            'business_id' => $referral->business_id,
            'business_name_snapshot' => (string) (Business::whereKey($referral->business_id)->value('name') ?? ''),
            'period_year' => (int) now()->format('Y'),
            'period_month' => (int) now()->format('n'),
            'base_amount' => 0,
            'currency' => AffiliateSettings::payoutCurrency(),
            'rate' => 0,
            'commission_amount' => round($amount, 2),
            'kind' => $kind,
            'status' => 'approved',
            'notes' => $note,
            'created_by' => $adminId,
        ]);
    }

    /** Estimate clawback when a referred business is refunded. Idempotent per charge. */
    public function clawbackForRefund(int $businessId, string $chargeId, float $refundAmount, ?string $currency = null): ?AffiliateCommission
    {
        $refundAmount = round($refundAmount, 2);
        if ($refundAmount <= 0) {
            return null;
        }

        $referral = AffiliateReferral::with('partner')
            ->where('business_id', $businessId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();
        if (! $referral) {
            return null;
        }

        $marker = 'refund:' . $chargeId;
        if (AffiliateCommission::where('referral_id', $referral->id)->where('notes', 'like', "%$marker%")->exists()) {
            return null;
        }

        $rate = $referral->effectiveRate();

        return $this->addAdjustment(
            $referral,
            -1 * round($refundAmount * $rate / 100, 2),
            "Refund clawback (estimate) — $marker",
            null,
            'clawback'
        );
    }

    public function setCommissionStatus(AffiliateCommission $commission, string $status): bool
    {
        if (! in_array($status, ['pending', 'approved', 'void'], true)) {
            return false;
        }
        if ($commission->payout_id !== null || $commission->status === 'paid') {
            return false;
        }

        return $commission->update(['status' => $status]);
    }
}
