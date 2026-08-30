<?php

namespace App\Http\Controllers\SuperAdmin\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePartner;
use App\Models\AffiliatePayout;
use App\Models\AffiliateProspect;
use App\Models\AffiliateReferral;
use App\Models\Business;
use App\Models\Country;
use App\Services\Affiliate\AffiliateSettings;
use App\Services\Affiliate\AttributionService;
use App\Services\Affiliate\StatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function index(Request $request, StatsService $stats): View
    {
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $partners = AffiliatePartner::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('partner_code', 'like', "%$search%")
                ->orWhere('company_name', 'like', "%$search%")))
            ->when(in_array($status, ['pending', 'active', 'suspended', 'rejected'], true),
                fn ($q) => $q->where('status', $status))
            ->withCount(['referrals as approved_referrals' => fn ($q) => $q->where('status', 'approved')])
            ->orderByRaw("status = 'pending' DESC")
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.affiliate.index', [
            'partners' => $partners,
            'overview' => $stats->adminOverview(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.affiliate.partner-form', [
            'partner' => new AffiliatePartner(['commission_rate' => AffiliateSettings::defaultRate()]),
            'countries' => Country::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'unique:affiliate_partners,email'],
            'password' => ['required', Password::min(8)],
            'commission_rate' => ['required', 'numeric', 'between:0,100'],
            'status' => ['required', 'in:pending,active'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'payout_method' => ['nullable', 'string', 'max:40'],
            'payout_details' => ['nullable', 'string', 'max:255'],
        ]);

        $partner = AffiliatePartner::create([
            ...$data,
            'partner_code' => $this->generateCode($data['name']),
            'agreement_accepted_at' => now(),
            'approved_at' => $data['status'] === 'active' ? now() : null,
            'approved_by' => $data['status'] === 'active' ? Auth::guard('admin')->id() : null,
        ]);

        return redirect()->route('superadmin.affiliate.partners.show', $partner)
            ->with('status', "Partner created — referral code {$partner->partner_code}.");
    }

    public function show(Request $request, AffiliatePartner $partner, StatsService $stats): View
    {
        $tab = $request->query('tab', 'referrals');

        return view('superadmin.affiliate.partner-show', [
            'partner' => $partner,
            'tab' => $tab,
            'stats' => $stats->forPartner($partner),
            'referrals' => AffiliateReferral::with(['business:id,name,is_active,plan_id', 'business.plan:id,name'])
                ->where('partner_id', $partner->id)->latest()->get(),
            'prospects' => AffiliateProspect::where('partner_id', $partner->id)
                ->latest('claimed_at')->limit(100)->get(),
            'commissions' => AffiliateCommission::with('business:id,name')
                ->where('partner_id', $partner->id)
                ->orderByDesc('period_year')->orderByDesc('period_month')->orderByDesc('id')
                ->limit(200)->get(),
            'payouts' => AffiliatePayout::where('partner_id', $partner->id)
                ->orderByDesc('period_year')->orderByDesc('period_month')->get(),
            'attachable' => Business::whereDoesntHave('affiliateReferral')->orderBy('name')->limit(500)->get(['id', 'name']),
        ]);
    }

    public function edit(AffiliatePartner $partner): View
    {
        return view('superadmin.affiliate.partner-form', [
            'partner' => $partner,
            'countries' => Country::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, AffiliatePartner $partner): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'commission_rate' => ['required', 'numeric', 'between:0,100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'payout_method' => ['nullable', 'string', 'max:40'],
            'payout_details' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'new_password' => ['nullable', Password::min(8)],
        ]);

        $partner->update(collect($data)->except('new_password')->all());

        if (! empty($data['new_password'])) {
            $partner->update(['password' => $data['new_password']]);
        }

        return redirect()->route('superadmin.affiliate.partners.show', $partner)
            ->with('status', 'Partner updated.');
    }

    public function setStatus(Request $request, AffiliatePartner $partner): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended,rejected']]);

        $partner->update([
            'status' => $data['status'],
            'approved_at' => $data['status'] === 'active' ? ($partner->approved_at ?? now()) : $partner->approved_at,
            'approved_by' => $data['status'] === 'active' ? ($partner->approved_by ?? Auth::guard('admin')->id()) : $partner->approved_by,
        ]);

        return back()->with('status', "Partner status set to {$data['status']}.");
    }

    public function attachBusiness(Request $request, AffiliatePartner $partner, AttributionService $attribution): RedirectResponse
    {
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'rate' => ['nullable', 'numeric', 'between:0,100'],
            'approve' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $attribution->attachManual(
            $data['business_id'], $partner->id,
            $data['rate'] ?? null, (bool) ($data['approve'] ?? false),
            Auth::guard('admin')->id(), $data['note'] ?? null
        );

        return redirect()->route('superadmin.affiliate.partners.show', ['partner' => $partner, 'tab' => 'referrals'])
            ->with('status', $result['ok'] ? 'Business attached to partner.' : $result['error']);
    }

    public function impersonate(AffiliatePartner $partner): RedirectResponse
    {
        session(['affiliate_impersonated_by' => Auth::guard('admin')->id()]);
        Auth::guard('affiliate')->login($partner);

        return redirect()->route('affiliate.portal.dashboard');
    }

    private function generateCode(string $name): string
    {
        $base = strtoupper(Str::of($name)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 6));
        if (strlen($base) < 3) {
            $base = 'PART' . $base;
        }
        for ($i = 0; $i < 40; $i++) {
            $code = substr($base, 0, 6) . strtoupper(Str::random($i < 8 ? 3 : 5));
            if (! AffiliatePartner::where('partner_code', $code)->exists()) {
                return $code;
            }
        }

        return 'P' . strtoupper(Str::random(9));
    }
}
