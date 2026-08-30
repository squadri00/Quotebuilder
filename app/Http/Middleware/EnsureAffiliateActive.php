<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A partner may sign in while still `pending`, but only ever sees the
 * "awaiting approval" screen until a super admin activates them.
 * Suspended / rejected partners are logged straight back out.
 */
class EnsureAffiliateActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $partner = Auth::guard('affiliate')->user();

        if (! $partner) {
            return redirect()->route('affiliate.portal.login');
        }

        if (in_array($partner->status, ['suspended', 'rejected'], true)) {
            Auth::guard('affiliate')->logout();
            $request->session()->forget('affiliate_impersonated_by');

            return redirect()->route('affiliate.portal.login')
                ->with('status', 'Your partner account is not active.');
        }

        if ($partner->status === 'pending' && ! $request->routeIs('affiliate.portal.pending', 'affiliate.portal.logout')) {
            return redirect()->route('affiliate.portal.pending');
        }

        return $next($request);
    }
}
