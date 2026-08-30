<?php

namespace App\Http\Controllers\SuperAdmin\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePartner;
use App\Models\AffiliateProspect;
use App\Services\Affiliate\ProspectService;
use App\Services\Affiliate\StatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProspectController extends Controller
{
    public function index(Request $request, StatsService $stats): View
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status');
        $partnerId = $request->integer('partner') ?: null;

        $rows = AffiliateProspect::with('partner:id,name,partner_code')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('company_name', 'like', "%$q%")
                ->orWhere('city', 'like', "%$q%")
                ->orWhere('contact_name', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")))
            ->when(in_array($status, ['open', 'working', 'won', 'lost', 'expired', 'released'], true),
                fn ($query) => $query->where('status', $status))
            ->when($partnerId, fn ($query) => $query->where('partner_id', $partnerId))
            ->latest('claimed_at')
            ->paginate(30)
            ->withQueryString();

        return view('superadmin.affiliate.prospects', [
            'rows' => $rows,
            'partners' => AffiliatePartner::orderBy('name')->get(['id', 'name', 'partner_code']),
            'overlap' => $stats->overlapReport(),
            'q' => $q,
            'statusFilter' => $status,
            'partnerFilter' => $partnerId,
        ]);
    }

    public function update(Request $request, AffiliateProspect $prospect, ProspectService $prospects): RedirectResponse
    {
        $action = $request->input('action');
        $adminId = Auth::guard('admin')->id();

        if ($action === 'release') {
            $prospects->release($prospect->id, null, $adminId);

            return back()->with('status', 'Claim released.');
        }

        if ($action === 'extend') {
            $prospect->update([
                'claim_expires_at' => $prospects->claimExpiryFrom(),
                'last_activity_at' => now(),
            ]);

            return back()->with('status', 'Claim extended.');
        }

        if ($action === 'reassign') {
            $data = $request->validate(['partner_id' => ['required', 'exists:affiliate_partners,id']]);
            $prospect->update(['partner_id' => $data['partner_id'], 'last_activity_at' => now()]);

            return back()->with('status', 'Prospect reassigned.');
        }

        return back();
    }
}
