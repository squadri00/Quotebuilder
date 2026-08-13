<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Business;
use App\Models\PlatformSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // We register our own webhook route pointing at a subclassed
        // controller (app/Http/Controllers/Stripe/WebhookController.php)
        // that also syncs businesses.plan_id — must happen in register(),
        // not boot(), so the flag is set before CashierServiceProvider's
        // own boot() checks it and registers its default routes.
        Cashier::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyPlatformSettings();

        // Default limiter for public, unauthenticated routes (e.g. the
        // future customer-facing quote builder). Apply with the
        // 'throttle:public' middleware on any route/group that doesn't
        // require login. 60/min per IP is a starting point — raise or
        // lower per route as needed.
        RateLimiter::for('public', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Subscriptions belong to the business/tenant, not to whichever
        // individual user happens to be logged in.
        Cashier::useCustomerModel(Business::class);

        // 'dashboard' needs it too: its content is captured into a slot and
        // evaluated in its own view context before layouts.app ever renders,
        // so a composer scoped to layouts.app alone never reaches it.
        View::composer(['layouts.app', 'dashboard'], function ($view) {
            $business = Auth::check() ? Auth::user()->business : null;

            if (! $business || ! Auth::user()->hasPermission(\App\Support\TeamPermissions::ANNOUNCEMENTS)) {
                $view->with(['headerAnnouncementCount' => 0, 'headerAnnouncements' => collect()]);
                return;
            }

            $userId = Auth::id();

            // Count first: fetching the list below marks items read as a
            // side effect, which would otherwise zero out the badge before
            // it's rendered.
            $count = Announcement::unreadCountForBusiness($business, $userId);
            $announcements = Announcement::unreadForBusiness($business, $userId);

            $view->with(['headerAnnouncementCount' => $count, 'headerAnnouncements' => $announcements]);
        });
    }

    /**
     * Lets the Super Admin manage Stripe/SMTP credentials from the UI
     * (see SuperAdmin\PlatformSettingController) instead of editing .env.
     * Every field is optional here — anything left blank simply keeps
     * whatever .env already provides, so nothing breaks before these are
     * filled in, and .env still works as a fallback/override path for a
     * developer who prefers it.
     *
     * Guarded by hasTable() because this runs on every boot, including
     * `artisan migrate` itself before this table exists yet on a fresh
     * install.
     */
    private function applyPlatformSettings(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $settings = PlatformSetting::first();

        if (! $settings) {
            return;
        }

        // Lets both sidebars (business-side and Super Admin) render the
        // platform's own logo/version without every layout having to query
        // for it itself — see layouts/sidebar-navigation.blade.php and
        // layouts/superadmin.blade.php.
        View::share('platformSettings', $settings);

        if ($settings->platform_name) {
            config(['app.name' => $settings->platform_name]);
        }

        if ($settings->stripe_key) {
            config(['cashier.key' => $settings->stripe_key]);
        }

        if ($settings->stripe_secret) {
            config(['cashier.secret' => $settings->stripe_secret]);
        }

        if ($settings->stripe_webhook_secret) {
            config(['cashier.webhook.secret' => $settings->stripe_webhook_secret]);
        }

        if ($settings->default_currency) {
            config(['cashier.currency' => $settings->default_currency]);
        }

        if ($settings->mail_mailer) {
            config(['mail.default' => $settings->mail_mailer]);
        }

        if ($settings->mail_host) {
            config([
                'mail.mailers.smtp.host' => $settings->mail_host,
                'mail.mailers.smtp.port' => $settings->mail_port,
                'mail.mailers.smtp.username' => $settings->mail_username,
                'mail.mailers.smtp.password' => $settings->mail_password,
                'mail.mailers.smtp.scheme' => $settings->mail_scheme,
            ]);
        }

        if ($settings->mail_from_address) {
            config([
                'mail.from.address' => $settings->mail_from_address,
                'mail.from.name' => $settings->mail_from_name ?: config('mail.from.name'),
            ]);
        }
    }
}
