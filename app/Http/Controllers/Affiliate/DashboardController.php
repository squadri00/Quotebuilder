<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Services\Affiliate\AffiliateSettings;
use App\Services\Affiliate\PayoutService;
use App\Services\Affiliate\StatsService;
use Illuminate\View\View;

class DashboardController extends PortalController
{
    public function index(StatsService $stats, PayoutService $payouts): View
    {
        $partner = $this->partner();

        return view('affiliate.portal.dashboard', [
            'partner' => $partner,
            'stats' => $stats->forPartner($partner),
            'cookieDays' => AffiliateSettings::cookieDays(),
            'eligible' => $payouts->eligibleMonths($partner->id),
            'recentReferrals' => AffiliateReferral::with(['business:id,name,is_active', 'business.plan:id,name'])
                ->where('partner_id', $partner->id)->latest()->limit(6)->get(),
            'recentCommissions' => AffiliateCommission::with('business:id,name')
                ->where('partner_id', $partner->id)->latest()->limit(6)->get(),
        ]);
    }

    public function pending(): View|\Illuminate\Http\RedirectResponse
    {
        $partner = $this->partner();

        if ($partner->status === 'active') {
            return redirect()->route('affiliate.portal.dashboard');
        }

        return view('affiliate.portal.pending', ['partner' => $partner]);
    }
}
