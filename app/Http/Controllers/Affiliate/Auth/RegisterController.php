<?php

namespace App\Http\Controllers\Affiliate\Auth;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePartner;
use App\Models\Country;
use App\Services\Affiliate\AffiliateSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! AffiliateSettings::enabled()) {
            return redirect()->route('affiliate.portal.login')
                ->with('status', 'The partner program is not accepting applications right now.');
        }

        return view('affiliate.portal.auth.register', [
            'countries' => Country::orderBy('name')->pluck('name'),
            'termsUrl' => AffiliateSettings::termsUrl(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(AffiliateSettings::enabled(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', 'unique:affiliate_partners,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
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
            'agree' => ['accepted'],
        ]);

        $autoApprove = AffiliateSettings::autoApprovePartners();

        $partner = AffiliatePartner::create([
            ...collect($data)->except(['password_confirmation', 'agree'])->all(),
            'partner_code' => $this->generateCode($data['name']),
            'password' => $data['password'],
            'commission_rate' => AffiliateSettings::defaultRate(),
            'status' => $autoApprove ? 'active' : 'pending',
            'agreement_accepted_at' => now(),
            'approved_at' => $autoApprove ? now() : null,
        ]);

        Auth::guard('affiliate')->login($partner);

        return $autoApprove
            ? redirect()->route('affiliate.portal.dashboard')
                ->with('status', "You're in — your referral code is {$partner->partner_code}.")
            : redirect()->route('affiliate.portal.pending')
                ->with('status', "Application received — your referral code is {$partner->partner_code}.");
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
