<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Scheduled drift check.
 *
 *   - Comparator (SYNC_LIVE_ENDPOINT set): full local-vs-live compare.
 *   - Live server (no endpoint): self-check — behind the remote? un-run
 *     migrations?
 *
 * On drift it writes the banner flag, logs a warning, and e-mails the admin
 * (debounced by SYNC_REALERT_HOURS).
 */
class SyncCheck extends Command
{
    protected $signature = 'sync:check';

    protected $description = 'Check whether this environment is in sync with the other one';

    public function handle(): int
    {
        $cfg = SyncEngine::config();
        if ($cfg['secret'] === '') {
            $this->warn('Environment sync not configured (SYNC_SHARED_SECRET) — nothing to do.');

            return self::SUCCESS;
        }

        $root  = SyncEngine::projectRoot();
        $local = SyncEngine::fingerprint(['fetch' => $cfg['live_endpoint'] !== '']);
        $branch = $local['git']['branch'] ?? 'main';
        $trueOrigin = SyncEngine::remoteHead($root, $branch);

        $issues = [];
        $mode = 'selfcheck';
        $reachable = null;

        if ($cfg['live_endpoint'] !== '') {
            $mode = 'compare';
            $r = SyncEngine::httpGet($cfg['live_endpoint'], $cfg['secret']);
            if (! $r['ok']) {
                $reachable = false;
                $issues[] = ['sev' => 'critical', 'area' => 'endpoint', 'msg' => 'Cannot reach the live sync endpoint: '.$r['error']];
            } else {
                $reachable = true;
                $issues = SyncEngine::diff($local, $r['data'], $trueOrigin)['issues'];
            }
        } else {
            if (! empty($local['git']['dirty'])) {
                $issues[] = ['sev' => 'critical', 'area' => 'git', 'msg' => $local['git']['dirty_count'].' uncommitted change(s) on this server.'];
            }
            if ($trueOrigin && ! empty($local['git']['head']) && $local['git']['head'] !== $trueOrigin) {
                $issues[] = ['sev' => 'critical', 'area' => 'git', 'msg' => 'This server is not on the latest commit — a pull is missing.'];
            }
            foreach ($local['db']['pending_migrations'] as $p) {
                $issues[] = ['sev' => 'critical', 'area' => 'migrations', 'msg' => 'Un-run migration: '.$p['filename']];
            }
            if (empty($local['db']['ledger_installed'])) {
                $issues[] = ['sev' => 'critical', 'area' => 'migrations', 'msg' => 'migrations table not created.'];
            }
        }

        $actionable = array_values(array_filter($issues, fn ($i) => in_array($i['sev'], ['critical', 'warn'], true)));
        $ok = count($actionable) === 0;
        $topMsgs = array_slice(array_map(fn ($i) => $i['msg'], $issues), 0, 5);

        SyncEngine::writeStatus($ok, $mode, $reachable, $topMsgs);

        $this->line(($ok ? 'IN SYNC' : 'OUT OF SYNC — '.count($actionable).' issue(s)')." ($mode)");
        foreach ($topMsgs as $m) {
            $this->line('  - '.$m);
        }

        if ($ok) {
            Cache::forget('sync.last_alert');
            Log::info("Environment sync: in sync ($mode).");

            return self::SUCCESS;
        }

        Log::warning("Environment sync drift ($mode): ".implode(' | ', $topMsgs), ['issues' => $issues]);

        // Debounce.
        $key = md5(json_encode($topMsgs));
        $last = Cache::get('sync.last_alert', []);
        $hoursSince = ! empty($last['at']) ? (time() - strtotime($last['at'])) / 3600 : PHP_INT_MAX;
        $reAlertDue = $cfg['realert_hours'] > 0 && $hoursSince >= $cfg['realert_hours'];

        if (($last['key'] ?? null) === $key && ! $reAlertDue) {
            $this->line('(alert already sent for this exact drift; not re-sending)');

            return self::SUCCESS;
        }

        $to = $cfg['alert_email'];
        if ($to) {
            $lines = array_map(fn ($i) => strtoupper($i['sev']).' ['.$i['area'].'] '.$i['msg'], $issues);
            $body = "The Quotaire environments are OUT OF SYNC ($mode check on {$local['host']}).\n\n"
                .implode("\n", $lines)
                ."\n\nOpen Super Admin -> Environment Sync to review and fix.";
            try {
                Mail::raw($body, fn ($m) => $m->to($to)->subject('Quotaire: environments out of sync'));
                $this->line("emailed $to");
            } catch (\Throwable $e) {
                $this->warn('email failed: '.$e->getMessage());
            }
        }

        Cache::forever('sync.last_alert', ['at' => gmdate('c'), 'key' => $key]);

        return self::SUCCESS;
    }
}
