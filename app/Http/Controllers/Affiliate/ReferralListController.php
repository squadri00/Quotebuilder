<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\AffiliateReferral;
use App\Models\TaxCollection;
use Illuminate\View\View;

class ReferralListController extends PortalController
{
    public function index(): View
    {
        $partner = $this->partner();

        $rows = AffiliateReferral::with(['business:id,name,is_active,created_at,plan_id', 'business.plan:id,name'])
            ->where('partner_id', $partner->id)
            ->latest()
            ->get()
            ->map(function (AffiliateReferral $r) {
                $r->lifetime_commission = $r->commissions()->where('status', '!=', 'void')->sum('commission_amount');
                $r->last_payment = TaxCollection::where('business_id', $r->business_id)->max('collected_at');

                return $r;
            });

        return view('affiliate.portal.referrals', compact('partner', 'rows'));
    }
}
