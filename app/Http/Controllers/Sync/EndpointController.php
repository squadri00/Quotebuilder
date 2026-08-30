<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Secret-header authenticated endpoints the OTHER environment's comparator
 * calls. No login session — auth is the X-Sync-Token header.
 *
 *   GET  /sync/endpoint                       -> compact fingerprint (5-min cache)
 *   GET  /sync/endpoint?fresh=1               -> recompute now
 *   GET  /sync/endpoint?detail=files          -> + full file manifest
 *   GET  /sync/endpoint?detail=config&table=X -> rows of one watched table
 *   POST /sync/apply   {"confirm":true}       -> back up DB, then migrate --force
 */
class EndpointController extends Controller
{
    public function endpoint(Request $request): JsonResponse
    {
        @set_time_limit(120);

        if (($fail = $this->authFail($request, 'secret'))) {
            return $fail;
        }

        $detail = (string) $request->query('detail', '');

        if ($detail === 'config') {
            $table = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $request->query('table', '')));
            if (! in_array($table, SyncEngine::watchedTables(), true)) {
                return response()->json(['error' => 'Unknown or non-watched table.'], 400);
            }

            return response()->json([
                'table' => $table,
                'rows'  => SyncEngine::configTableRows($table),
                'generated_at' => gmdate('c'),
            ]);
        }

        $fresh = filter_var($request->query('fresh'), FILTER_VALIDATE_BOOLEAN);

        return response()->json(SyncEngine::fingerprintCached([
            'include_manifest' => $detail === 'files',
            'fresh'            => $fresh,
        ]));
    }

    public function apply(Request $request): JsonResponse
    {
        @set_time_limit(600);

        if (($fail = $this->authFail($request, 'apply_secret'))) {
            return $fail;
        }
        if (! filter_var($request->input('confirm'), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json(['ok' => false, 'error' => 'Missing {"confirm": true}.'], 400);
        }

        $pending = SyncEngine::pendingMigrations();
        $ledgerMissing = ! SyncEngine::migrationsTableExists();

        $log = [];
        if ($pending || $ledgerMissing) {
            $backup = SyncEngine::backupDatabase('pre-migrate');
            if (! $backup['ok']) {
                Log::error('sync.apply: backup failed', $backup);

                return response()->json([
                    'ok' => false,
                    'error' => 'Database backup failed — migrations were NOT run. '.$backup['error'],
                ]);
            }
            $log[] = 'Backup: '.basename((string) $backup['path'])
                .' ('.number_format(($backup['size'] ?? 0) / 1024, 1).' KB, '.$backup['method'].').';
        } else {
            $log[] = 'Nothing pending — no backup taken.';
        }

        $res = SyncEngine::runMigrate();
        $log[] = $res['output'] ?: ($res['ok'] ? 'migrate: nothing to migrate.' : 'migrate: no output.');

        $after = array_column(SyncEngine::pendingMigrations(), 'filename');

        return response()->json([
            'ok'            => $res['ok'] && ! $after,
            'log'           => $log,
            'pending_after' => $after,
        ]);
    }

    /**
     * Returns a JsonResponse when auth fails, or null when it passes.
     */
    private function authFail(Request $request, string $which): ?JsonResponse
    {
        $cfg = SyncEngine::config();
        $secret = $cfg[$which === 'apply_secret' ? 'apply_secret' : 'secret'] ?? '';

        if ($secret === '') {
            return response()->json(
                ['error' => 'Environment sync is not configured on this server (set SYNC_SHARED_SECRET).'], 503
            );
        }

        $ip = (string) $request->ip();
        if (RateLimiter::tooManyAttempts('sync-endpoint:'.$ip, 30)) {
            return response()->json(['error' => 'Rate limited.'], 429);
        }

        $given = (string) ($request->header('X-Sync-Token') ?? $request->query('token', ''));
        if (! hash_equals((string) $secret, $given)) {
            RateLimiter::hit('sync-endpoint:'.$ip, 30);

            return response()->json(['error' => 'Bad or missing X-Sync-Token.'], 401);
        }

        return null;
    }
}
