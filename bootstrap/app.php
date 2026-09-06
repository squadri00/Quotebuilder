<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;

// This server (XAMPP on Windows, mod_php) runs multiple Laravel apps in the
// same Apache process, and Apache's WinNT MPM serves ALL sites from one
// process with many shared threads. Laravel's .env loader normally also
// writes values via PHP's putenv(), which mutates that shared OS-level
// process environment — so a value set while handling a request for one
// app can leak into a later request for a *different* app on this same
// server (this is exactly what caused quotebuilder to briefly query
// Chantley's database on 2026-09-06). Disabling putenv keeps env values
// confined to the current request's own $_ENV/$_SERVER, which Apache
// already resets per-request, so they can no longer cross between apps.
Env::disablePutenv();

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
        // If a Super Admin's session sits idle long enough that the CSRF
        // token on the logout form expires before they click it, Laravel
        // would otherwise show the raw "419 Page Expired" error page for
        // what is really just an already-effectively-logged-out session.
        // Send them to the main site instead — scoped to just this one
        // route so every other 419 elsewhere keeps its normal behavior.
        // Note: by the time renderable callbacks run, Laravel's Handler has
        // already converted TokenMismatchException into a generic
        // Symfony HttpException(419) (see Handler::prepareException()),
        // so that's what has to be type-hinted here, not the original
        // TokenMismatchException.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && $request->routeIs('superadmin.logout')) {
                return redirect('/');
            }
        });
    })->create();
