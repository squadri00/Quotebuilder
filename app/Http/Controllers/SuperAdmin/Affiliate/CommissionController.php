<?php

namespace App\Http\Controllers\SuperAdmin\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePartner;
use App\Models\AffiliateReferral;
use App\Services\Affiliate\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function __construct(private CommissionService $commissions)
    {
    }

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'referrals');
        $status = $request->query('status');
        $partnerId = $request->integer('partner') ?: null;

        return view('superadmin.affiliate.commissions', [
            'tab' => $tab,
            'pendingReferrals' => AffiliateReferral::with(['partner:id,name,partner_code,commission_rate', 'business:id,name,is_active'])
                ->where('status', 'pending')
                ->withCount('commissions as accrued_lines')
                ->withSum('commissions as accrued_total', 'commission_amount')
                ->oldest()->get(),
            'ledger' => AffiliateCommission::with(['partner:id,partner_code', 'business:id,name', 'payout:id,payout_number'])
                ->when(in_array($status, ['pending', 'approved', 'on_payout', 'paid', 'void'], true),
                    fn ($q) => $q->where('status', $status))
                ->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId))
                ->latest()->paginate(40)->withQueryString(),
            'partners' => AffiliatePartner::orderBy('name')->get(['id', 'name', 'partner_code']),
            'approvedRefs' => AffiliateReferral::with(['partner:id,partner_code', 'business:id,name'])
                ->whereIn('status', ['approved', 'pending'])->get(),
            'status' => $status,
            'partnerFilter' => $partnerId,
        ]);
    }

    public function updateReferral(Request $request, AffiliateReferral $referral): RedirectResponse
    {
        $action = $request->input('action');
        $adminId = Auth::guard('admin')->id();

        if ($action === 'approve') {
            $this->commissions->approveReferral($referral, $adminId);

            return redirect()->route('superadmin.affiliate.commissions.index', ['tab' => 'referrals'])
                ->with('status', 'Referral approved — its commissions are now payable.');
        }

        if ($action === 'reject') {
            $this->commissions->rejectReferral($referral, $request->input('reason'));

            return redirect()->route('superadmin.affiliate.commissions.index', ['tab' => 'referrals'])
                ->with('status', 'Referral rejected.');
        }

        return back();
    }

    public function updateCommission(Request $request, AffiliateCommission $commission): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,void']]);
        $this->commissions->setCommissionStatus($commission, $data['status']);

        return back()->with('status', 'Commission line updated.');
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'referral_id' => ['required', 'exists:affiliate_referrals,id'],
            'amount' => ['required', 'numeric'],
            'kind' => ['required', 'in:adjustment,clawback'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $referral = AffiliateReferral::findOrFail($data['referral_id']);
        $this->commissions->addAdjustment(
            $referral, (float) $data['amount'], $data['note'] ?? null,
            Auth::guard('admin')->id(), $data['kind']
        );

        return redirect()->route('superadmin.affiliate.commissions.index', ['tab' => 'ledger'])
            ->with('status', 'Adjustment added.');
    }
}
