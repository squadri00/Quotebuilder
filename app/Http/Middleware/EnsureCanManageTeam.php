<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Team management (inviting, changing roles/product access, deactivating)
 * is Owner-only. Applied to the whole 'team.*' route group rather than
 * repeated in every TeamController action.
 */
class EnsureCanManageTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::guard('web')->user()?->canManageTeam(), 404);

        return $next($request);
    }
}
