<?php

namespace App\Http\Middleware;

use App\Support\TeamPermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generic — takes the permission key as a route middleware parameter
 * (e.g. 'permission:tax_rates') rather than needing a new middleware
 * class per grantable area. The Owner always passes (see
 * User::hasPermission()); a Member needs the key explicitly granted.
 */
class EnsureHasPermission
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        if (! in_array($key, TeamPermissions::keys(), true)) {
            throw new InvalidArgumentException("Unknown permission key: {$key}");
        }

        abort_unless(Auth::guard('web')->user()?->hasPermission($key), 404);

        return $next($request);
    }
}
