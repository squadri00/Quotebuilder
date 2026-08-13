<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Billing (subscriptions, payment methods, invoices) is Owner-only — an
 * Admin gets full operational access to the rest of the business but
 * never sees payment details. Applied to the whole 'billing.*' route
 * group rather than repeated in every BillingController action.
 */
class EnsureCanAccessBilling
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::guard('web')->user()?->canAccessBilling(), 404);

        return $next($request);
    }
}
