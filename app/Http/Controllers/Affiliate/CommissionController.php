<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\AffiliateCommission;
use App\Services\Affiliate\AffiliateSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends PortalController
{
    public function index(Request $request): View
    {
        $partner = $this->partner();
        $ccy = AffiliateSettings::payoutCurrency();
        $status = $request->query('status');

        $rows = AffiliateCommission::with(['business:id,name', 'payout:id,payout_number'])
            ->where('partner_id', $partner->id)
            ->when(in_array($status, ['pending', 'approved', 'on_payout', 'paid', 'void'], true),
                fn ($q) => $q->where('status', $status))
            ->orderByDesc('period_year')->orderByDesc('period_month')->orderByDesc('id')
            ->limit(500)->get();

        $totals = AffiliateCommission::where('partner_id', $partner->id)->where('currency', $ccy)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status='pending'   THEN commission_amount END),0) as pending,
                COALESCE(SUM(CASE WHEN status='approved'  THEN commission_amount END),0) as approved,
                COALESCE(SUM(CASE WHEN status='on_payout' THEN commission_amount END),0) as on_payout,
                COALESCE(SUM(CASE WHEN status='paid'      THEN commission_amount END),0) as paid
            ")->first();

        return view('affiliate.portal.commissions', compact('partner', 'rows', 'totals', 'status'));
    }
}
