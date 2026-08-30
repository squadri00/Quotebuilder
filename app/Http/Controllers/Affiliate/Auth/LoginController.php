<?php

namespace App\Http\Controllers\Affiliate\Auth;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePartner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('affiliate.portal.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'affiliate-login:' . strtolower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Please wait a minute and try again.',
            ]);
        }

        $partner = AffiliatePartner::where('email', $data['email'])->first();

        if (! $partner || ! password_verify($data['password'], $partner->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email or password is incorrect.']);
        }

        if (in_array($partner->status, ['suspended', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'email' => $partner->status === 'suspended'
                    ? 'This partner account is suspended.'
                    : 'This application was not approved.',
            ]);
        }

        RateLimiter::clear($key);
        Auth::guard('affiliate')->login($partner, true);
        $partner->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return $partner->status === 'active'
            ? redirect()->intended(route('affiliate.portal.dashboard'))
            : redirect()->route('affiliate.portal.pending');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('affiliate')->logout();
        $request->session()->forget('affiliate_impersonated_by');

        return redirect()->route('affiliate.portal.login');
    }
}
