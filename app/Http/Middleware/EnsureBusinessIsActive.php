<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks out an already-logged-in business user the moment a super admin
 * deactivates their account or their business — not just on their next
 * login attempt. Applied to every authenticated business route.
 */
class EnsureBusinessIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && (! $user->is_active || ! $user->business?->is_active)) {
            Auth::guard('web')->logout();

            // Not session()->invalidate(): that wipes the *entire* session,
            // including an unrelated 'admin' guard login — which would end
            // a super admin's own session if this fires while they're
            // impersonating a business that gets deactivated mid-session.
            // regenerate() rotates the session ID without clearing guards.
            $request->session()->regenerate();
            $request->session()->forget(['impersonating_business_id', 'impersonating_business_name']);

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Please contact support.',
            ]);
        }

        return $next($request);
    }
}
