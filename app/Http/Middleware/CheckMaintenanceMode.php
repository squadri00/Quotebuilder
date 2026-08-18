<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A database-backed stand-in for `php artisan down` — chosen specifically
 * because Super Admin won't have shell/SSH access on the live server (see
 * PlatformSettingController), so a toggle Super Admin can flip from the
 * Settings page is the only way this can actually be controlled there.
 *
 * Deliberately narrow: this only blocks LOGGING IN and PURCHASING, never
 * browsing. Everything else keeps working while this is on — the
 * marketing site, every public quote page and embedded widget, and
 * anyone already logged into a business dashboard. The first version of
 * this took the whole site down (login, dashboards, public quote pages,
 * everything) — wrong; a maintenance window on the backend shouldn't
 * stop a business's own customers from browsing and getting quotes, or
 * stop someone already signed in from working. Only two things actually
 * need to be off: nobody new should be able to sign in or start paying
 * for something while whatever's being worked on is in flux.
 *
 * $blockedPatterns lists exactly what's blocked — an allowlist-by-
 * omission (everything not listed here just passes through), the
 * opposite of the original version's "block everything except a few
 * exceptions." /superadmin/* is never touched by this check at all,
 * checked first and unconditionally, so Super Admin can always log in
 * and turn this back off regardless of what's in the blocklist below.
 */
class CheckMaintenanceMode
{
    /**
     * @var array<int, string>
     */
    private array $blockedPatterns = [
        // Logging in / signing up
        'login',
        'register',
        'register/*',
        'forgot-password',
        'forgot-password/*',
        'reset-password',
        'reset-password/*',

        // Purchasing — a new business paying to sign up, or an existing
        // one changing/buying anything through Stripe
        'checkout/*',
        'billing/subscribe/*',
        'billing/swap/*',
        'billing/support/subscribe',
        'billing/implementation/*',
        'billing/portal',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('superadmin*', 'stripe/webhook', 'up')) {
            return $next($request);
        }

        $settings = PlatformSetting::get();

        if (! $settings->maintenance_mode || ! $request->is($this->blockedPatterns)) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'platformSettings' => $settings,
        ], 503);
    }
}
