<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Read-only self-diagnosis page for the Super Admin — everything here
 * is safe to display (no secrets: passwords, API secrets, and APP_KEY
 * are never shown, matching the "secrets never redisplayed" rule
 * already used in Platform Settings) so the site owner can check what
 * the server is currently configured with, or hand these details to a
 * developer/host, without opening any files.
 */
class SystemInfoController extends Controller
{
    public function index(Request $request): View
    {
        $databaseStatus = $this->checkDatabase();
        $settings = PlatformSetting::first();

        return view('superadmin.system-info.index', [
            'appEnv' => config('app.env'),
            'appDebug' => config('app.debug'),
            'appUrl' => config('app.url'),
            'requestIsSecure' => $request->isSecure(),
            'phpVersion' => phpversion(),
            'laravelVersion' => app()->version(),
            'databaseStatus' => $databaseStatus,
            'databaseDriver' => config('database.default'),
            'queueConnection' => config('queue.default'),
            'pendingJobs' => $this->safeCount('jobs'),
            'failedJobs' => $this->safeCount('failed_jobs'),
            'sessionDriver' => config('session.driver'),
            'sessionSecureCookie' => config('session.secure') === true ? 'On' : (config('session.secure') === false ? 'Off' : 'Not set'),
            'cacheStore' => config('cache.default'),
            'logChannel' => config('logging.default'),
            'logStack' => implode(', ', config('logging.channels.stack.channels', [])),
            'logLevel' => config('logging.channels.' . config('logging.default') . '.level', config('logging.channels.daily.level', config('logging.channels.single.level'))),
            'mailMailer' => config('mail.default'),
            'mailFromAddress' => config('mail.from.address'),
            'stripeConfigured' => filled(config('cashier.key')) && filled(config('cashier.secret')),
            'stripeMode' => $this->stripeMode(),
            'webhookSecretConfigured' => filled(config('cashier.webhook.secret')),
            // file_exists() rather than is_link()/is_dir() — on some
            // Windows setups PHP's symlink-detection functions don't
            // recognize a working junction/symlink even though the
            // path resolves fine, while file_exists() does.
            'storageLinkExists' => file_exists(public_path('storage')),
            'diskFreeGb' => $this->diskSpaceGb(disk_free_space(base_path())),
            'diskTotalGb' => $this->diskSpaceGb(disk_total_space(base_path())),
            'settings' => $settings,
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['connected' => true, 'message' => 'Connected'];
        } catch (Throwable $e) {
            return ['connected' => false, 'message' => 'Not connected'];
        }
    }

    /**
     * Both tables only exist once the queue migrations have run —
     * guard against a fresh install or a non-database queue driver.
     */
    private function safeCount(string $table): ?int
    {
        try {
            return DB::table($table)->count();
        } catch (Throwable $e) {
            return null;
        }
    }

    private function stripeMode(): ?string
    {
        $key = config('cashier.key');

        if (blank($key)) {
            return null;
        }

        if (str_starts_with($key, 'pk_live_')) {
            return 'Live';
        }

        if (str_starts_with($key, 'pk_test_')) {
            return 'Test';
        }

        return 'Unknown';
    }

    private function diskSpaceGb(false|float $bytes): ?float
    {
        if ($bytes === false) {
            return null;
        }

        return round($bytes / 1024 / 1024 / 1024, 1);
    }
}
