<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateClick;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePartner;
use App\Models\AffiliatePayout;
use App\Models\AffiliateProspect;
use App\Models\AffiliateReferral;
use App\Models\TaxCollection;
use Illuminate\Support\Facades\DB;

class StatsService
{
    public function forPartner(AffiliatePartner $partner): array
    {
        $ccy = AffiliateSettings::payoutCurrency();
        $pid = $partner->id;

        $clicks = AffiliateClick::where('partner_id', $pid)->count();
        $signups = AffiliateReferral::where('partner_id', $pid)->count();

        $activeReferrals = AffiliateReferral::where('partner_id', $pid)
            ->where('status', 'approved')
            ->whereHas('business', fn ($q) => $q->where('is_active', true))
            ->count();

        // Referred MRR — latest ex-tax platform charge in the last 45 days
        // per active referred business.
        $mrr = (float) TaxCollection::query()
            ->join('affiliate_referrals as r', 'r.business_id', '=', 'tax_collections.business_id')
            ->where('r.partner_id', $pid)->where('r.status', 'approved')
            ->where('tax_collections.collected_at', '>=', now()->subDays(45))
            ->select('tax_collections.business_id', DB::raw('MAX(tax_collections.base_amount) as m'))
            ->groupBy('tax_collections.business_id')
            ->get()->sum('m');

        $byStatus = AffiliateCommission::where('partner_id', $pid)->where('currency', $ccy)
            ->select('status', DB::raw('SUM(commission_amount) as amt'))
            ->groupBy('status')->pluck('amt', 'status');

        $get = fn ($k) => (float) ($byStatus[$k] ?? 0);

        $thisMonth = (float) AffiliateCommission::where('partner_id', $pid)->where('currency', $ccy)
            ->where('period_year', now()->year)->where('period_month', now()->month)
            ->where('status', '!=', 'void')->sum('commission_amount');

        return [
            'currency' => $ccy,
            'clicks' => $clicks,
            'signups' => $signups,
            'active_referrals' => $activeReferrals,
            'conversion_rate' => $clicks > 0 ? round($signups / $clicks * 100, 1) : 0.0,
            'referred_mrr' => round($mrr, 2),
            'commission_month' => round($thisMonth, 2),
            'commission_pending' => round($get('pending'), 2),
            'commission_approved' => round($get('approved'), 2),
            'commission_unpaid' => round($get('approved') + $get('on_payout'), 2),
            'commission_paid' => round($get('paid'), 2),
            'commission_lifetime' => round($get('pending') + $get('approved') + $get('on_payout') + $get('paid'), 2),
        ];
    }

    public function adminOverview(): array
    {
        return [
            'currency' => AffiliateSettings::payoutCurrency(),
            'partners_active' => AffiliatePartner::where('status', 'active')->count(),
            'partners_pending' => AffiliatePartner::where('status', 'pending')->count(),
            'referrals_pending' => AffiliateReferral::where('status', 'pending')->count(),
            'referrals_approved' => AffiliateReferral::where('status', 'approved')->count(),
            'claims_active' => AffiliateProspect::activeClaims()->count(),
            'liability_pending' => (float) AffiliateCommission::where('status', 'pending')->sum('commission_amount'),
            'liability_unpaid' => (float) AffiliateCommission::whereIn('status', ['approved', 'on_payout'])->sum('commission_amount'),
            'payouts_due' => (float) AffiliatePayout::whereIn('status', ['finalized', 'submitted'])->sum('amount'),
            'payouts_paid_ytd' => (float) AffiliatePayout::where('status', 'paid')
                ->whereYear('paid_at', now()->year)->sum('amount'),
        ];
    }

    /** Prospects worked by more than one partner in the last 180 days. */
    public function overlapReport()
    {
        return AffiliateProspect::query()
            ->select('company_name_norm',
                DB::raw('MAX(company_name) as company_name'),
                DB::raw('COUNT(DISTINCT partner_id) as partner_count'),
                DB::raw('COUNT(*) as claim_count'),
                DB::raw('MAX(claim_expires_at) as latest_expiry'))
            ->where('claimed_at', '>=', now()->subDays(180))
            ->groupBy('company_name_norm')
            ->havingRaw('COUNT(DISTINCT partner_id) > 1')
            ->orderByDesc('claim_count')
            ->get();
    }
}
