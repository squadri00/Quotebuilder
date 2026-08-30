<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Environment Sync endpoints — no session/CSRF/maintenance-mode
            // middleware; authenticated by the X-Sync-Token shared secret.
            Route::middleware('throttle:60,1')->group(base_path('routes/sync.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'business.active' => \App\Http\Middleware\EnsureBusinessIsActive::class,
            'team.manage' => \App\Http\Middleware\EnsureCanManageTeam::class,
            'billing.access' => \App\Http\Middleware\EnsureCanAccessBilling::class,
            'permission' => \App\Http\Middleware\EnsureHasPermission::class,
            'affiliate.active' => \App\Http\Middleware\EnsureAffiliateActive::class,
        ]);

        // Without this, Laravel's default guest-redirect always points at the
        // business "login" route, even for unauthenticated hits on
        // auth:admin-protected /superadmin/* routes. Send those to the
        // super admin login instead so the two portals stay visually and
        // functionally separate, as required.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('affiliate*')) {
                return route('affiliate.portal.login');
            }

            return $request->is('superadmin*')
                ? route('superadmin.login')
                : route('login');
        });

        // Mirror image of the above: an already-authenticated admin who
        // revisits /superadmin/login shouldn't be bounced to the business
        // dashboard (Laravel's default), and vice versa.
        $middleware->redirectUsersTo(function (Request $request) {
            return $request->is('superadmin*')
                ? route('superadmin.dashboard')
                : route('dashboard');
        });

        // Stripe's webhook POSTs have no CSRF token to send — the request
        // is authenticated instead via its Stripe-Signature header. Cashier's
        // own WebhookController (app/Http/Controllers/Stripe/WebhookController
        // extends it) applies VerifyWebhookSignature itself, in its
        // constructor, whenever cashier.webhook.secret is configured — see
        // Super Admin > Platform Settings.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        // See App\Http\Middleware\CheckMaintenanceMode's own docblock for
        // why this is a DB-backed Super-Admin toggle rather than
        // `artisan down` — appended (not prepended) so it always runs
        // after session/auth are available, since it needs the current
        // request's path to already be resolvable.
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        // Most production hosts (shared hosting, a managed VPS behind
        // Nginx/Certbot, a CDN) terminate TLS at a reverse proxy in
        // front of PHP, so the request Laravel actually sees is plain
        // HTTP. Without this, Laravel can't tell the original request
        // was HTTPS — breaking secure-cookie detection and generating
        // http:// links in emails/redirects. '*' trusts whatever proxy
        // sits in front of the app, which is the standard choice when
        // you don't control the exact proxy IP but trust the host's
        // own network (shared hosting, most managed VPS setups).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
