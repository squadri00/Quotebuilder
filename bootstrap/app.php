<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'business.active' => \App\Http\Middleware\EnsureBusinessIsActive::class,
            'team.manage' => \App\Http\Middleware\EnsureCanManageTeam::class,
            'billing.access' => \App\Http\Middleware\EnsureCanAccessBilling::class,
            'permission' => \App\Http\Middleware\EnsureHasPermission::class,
        ]);

        // Without this, Laravel's default guest-redirect always points at the
        // business "login" route, even for unauthenticated hits on
        // auth:admin-protected /superadmin/* routes. Send those to the
        // super admin login instead so the two portals stay visually and
        // functionally separate, as required.
        $middleware->redirectGuestsTo(function (Request $request) {
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
