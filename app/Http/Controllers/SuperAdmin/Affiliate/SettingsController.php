<?php

namespace App\Http\Controllers\SuperAdmin\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('superadmin.affiliate.settings', [
            'settings' => PlatformSetting::get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'affiliate_program_enabled' => ['nullable', 'boolean'],
            'affiliate_default_commission_rate' => ['required', 'numeric', 'between:0,100'],
            'affiliate_claim_days' => ['required', 'integer', 'between:1,365'],
            'affiliate_claim_renew_on_activity' => ['nullable', 'boolean'],
            'affiliate_cookie_days' => ['required', 'integer', 'between:1,365'],
            'affiliate_min_payout' => ['required', 'numeric', 'min:0'],
            'affiliate_auto_approve_partners' => ['nullable', 'boolean'],
            'affiliate_auto_approve_referrals' => ['nullable', 'boolean'],
            'affiliate_payout_currency' => ['nullable', 'string', 'size:3'],
            'affiliate_terms_url' => ['nullable', 'url', 'max:255'],
        ]);

        PlatformSetting::get()->update([
            'affiliate_program_enabled' => (bool) ($data['affiliate_program_enabled'] ?? false),
            'affiliate_default_commission_rate' => $data['affiliate_default_commission_rate'],
            'affiliate_claim_days' => $data['affiliate_claim_days'],
            'affiliate_claim_renew_on_activity' => (bool) ($data['affiliate_claim_renew_on_activity'] ?? false),
            'affiliate_cookie_days' => $data['affiliate_cookie_days'],
            'affiliate_min_payout' => $data['affiliate_min_payout'],
            'affiliate_auto_approve_partners' => (bool) ($data['affiliate_auto_approve_partners'] ?? false),
            'affiliate_auto_approve_referrals' => (bool) ($data['affiliate_auto_approve_referrals'] ?? false),
            'affiliate_payout_currency' => $data['affiliate_payout_currency'] ? strtoupper($data['affiliate_payout_currency']) : null,
            'affiliate_terms_url' => $data['affiliate_terms_url'] ?: null,
        ]);

        return back()->with('status', 'Referral program settings saved.');
    }
}
