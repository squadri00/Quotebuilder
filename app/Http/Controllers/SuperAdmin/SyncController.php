<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\View\View;

/**
 * Super Admin: compare this environment against the live server and show
 * exactly what is out of sync (git commits, Laravel migrations, watched
 * config tables, files). Offers "Push now" and "Back up & migrate live".
 */
class SyncController extends Controller
{
    public function index(Request $request): View
    {
        @set_time_limit(180);

        $cfg  = SyncEngine::config();
        $root = SyncEngine::projectRoot();

        $configured   = $cfg['secret'] !== '';
        $isComparator = $configured && $cfg['live_endpoint'] !== '';
        $forceFresh   = $request->boolean('fresh');

        $local       = SyncEngine::fingerprint(['fetch' => $isComparator]);
        $localBranch = $local['git']['branch'] ?? 'main';
        $trueOrigin  = SyncEngine::remoteHead($root, $localBranch);

        $live = $liveError = $liveCacheAge = $diff = null;

        if ($isComparator) {
            $url = $cfg['live_endpoint'].($forceFresh
                ? (str_contains($cfg['live_endpoint'], '?') ? '&' : '?').'fresh=1'
                : '');
            $r = SyncEngine::httpGet($url, $cfg['secret'], $forceFresh ? 90 : 45);
            if ($r['ok']) {
                $live = $r['data'];
                $liveCacheAge = ($live['_cache'] ?? '') === 'hit' ? (int) ($live['_cache_age'] ?? 0) : null;
                $diff = SyncEngine::diff($local, $live, $trueOrigin);
            } else {
                $liveError = $r['error'].(isset($r['raw']) ? ' — '.$r['raw'] : '');
            }
        }

        // Self-check verdict when we can't (or don't) compare.
        $selfIssues = [];
        $selfRemoteChecked = false;
        if (! $isComparator) {
            if (! empty($local['git']['dirty'])) {
                $selfIssues[] = ['sev' => 'critical', 'msg' => $local['git']['dirty_count'].' uncommitted change(s) on this machine.'];
            }
            if ($trueOrigin && ! empty($local['git']['head'])) {
                $selfRemoteChecked = true;
                if ($local['git']['head'] !== $trueOrigin) {
                    $ahead = (int) ($local['git']['ahead_origin'] ?? 0);
                    $selfIssues[] = $ahead > 0
                        ? ['sev' => 'critical', 'msg' => "$ahead commit(s) here are not pushed to the remote."]
                        : ['sev' => 'warn', 'msg' => 'This checkout is behind the remote — run git pull.'];
                }
            }
            foreach ($local['db']['pending_migrations'] as $p) {
                $selfIssues[] = ['sev' => 'critical', 'msg' => "Un-run migration: {$p['filename']} — run php artisan migrate"];
            }
            if (empty($local['db']['ledger_installed'])) {
                $selfIssues[] = ['sev' => 'critical', 'msg' => 'migrations table missing — run php artisan migrate'];
            }
        }

        // Persist the summary for the banner / health pill.
        $statusOk = $isComparator ? ($diff && $diff['ok'] && ! $liveError) : (count($selfIssues) === 0);
        $topMsgs  = [];
        if ($isComparator && $diff) {
            foreach (array_slice($diff['issues'], 0, 4) as $i) {
                $topMsgs[] = $i['msg'];
            }
        } elseif ($liveError) {
            $topMsgs[] = 'Could not reach the live sync endpoint.';
        } else {
            foreach (array_slice($selfIssues, 0, 4) as $i) {
                $topMsgs[] = $i['msg'];
            }
        }
        SyncEngine::writeStatus(
            $statusOk,
            $isComparator ? 'compare' : 'selfcheck',
            $isComparator ? ($liveError === null) : null,
            $topMsgs
        );

        // On-demand detail panels.
        $fileDiff = null;
        if ($request->query('view') === 'files' && $isComparator && ! $liveError) {
            $lm = SyncEngine::fileManifest($root)['files'];
            $rr = SyncEngine::httpGet(
                $cfg['live_endpoint'].(str_contains($cfg['live_endpoint'], '?') ? '&' : '?').'detail=files',
                $cfg['secret'], 120
            );
            $fileDiff = $rr['ok']
                ? ['ok' => true] + SyncEngine::manifestDiff($lm, $rr['data']['files']['manifest'] ?? [])
                : ['ok' => false, 'error' => $rr['error'] ?? '?'];
        }

        $configDiff = null;
        if ($request->query('view') === 'config' && $isComparator && ! $liveError) {
            $tbl = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $request->query('table', '')));
            $lr  = SyncEngine::configTableRows($tbl);
            $rr  = SyncEngine::httpGet(
                $cfg['live_endpoint'].(str_contains($cfg['live_endpoint'], '?') ? '&' : '?')
                .'detail=config&table='.urlencode($tbl),
                $cfg['secret'], 60
            );
            $rrows = $rr['ok'] ? ($rr['data']['rows'] ?? []) : [];
            $key = fn ($row) => isset($row['id']) ? 'id:'.$row['id'] : md5(json_encode($row));
            $lIdx = $rIdx = [];
            foreach ($lr as $row) {
                $lIdx[$key($row)] = $row;
            }
            foreach ($rrows as $row) {
                $rIdx[$key((array) $row)] = (array) $row;
            }
            $changed = [];
            foreach ($lIdx as $k => $row) {
                if (isset($rIdx[$k]) && json_encode($row) !== json_encode($rIdx[$k])) {
                    $changed[$k] = [$row, $rIdx[$k]];
                }
            }
            $configDiff = [
                'ok' => $rr['ok'],
                'error' => $rr['ok'] ? null : ($rr['error'] ?? '?'),
                'table' => $tbl,
                'only_local' => array_values(array_diff_key($lIdx, $rIdx)),
                'only_live'  => array_values(array_diff_key($rIdx, $lIdx)),
                'changed'    => $changed,
            ];
        }

        return view('superadmin.sync.index', compact(
            'cfg', 'configured', 'isComparator', 'forceFresh',
            'local', 'localBranch', 'trueOrigin',
            'live', 'liveError', 'liveCacheAge', 'diff',
            'selfIssues', 'selfRemoteChecked',
            'fileDiff', 'configDiff'
        ));
    }

    public function push(Request $request): RedirectResponse
    {
        $cfg  = SyncEngine::config();
        $root = SyncEngine::projectRoot();
        $isComparator = $cfg['secret'] !== '' && $cfg['live_endpoint'] !== '';

        if (! $isComparator) {
            return back()->with('sync_out', "Push is disabled here — this is the live/deploy server. It pulls, it never pushes.");
        }

        $branch = trim(Process::path($root)->run('git rev-parse --abbrev-ref HEAD')->output());
        $branch = preg_match('/^[\w.\/-]+$/', $branch) ? $branch : 'main';
        $out = Process::path($root)->run('git push origin '.escapeshellarg($branch));

        return back()->with('sync_out', "git push origin $branch\n\n".trim($out->output()."\n".$out->errorOutput()));
    }

    public function applyLive(Request $request): RedirectResponse
    {
        $cfg = SyncEngine::config();
        if ($cfg['live_apply_endpoint'] === '') {
            return back()->with('sync_out', 'No live apply endpoint configured (SYNC_LIVE_APPLY_ENDPOINT).');
        }

        $res = SyncEngine::httpPost($cfg['live_apply_endpoint'], $cfg['apply_secret'], ['confirm' => true]);

        $out = $res['ok'] && ($res['data'] ?? null)
            ? implode("\n", $res['data']['log'] ?? [])
                ."\n\nPending after: ".(implode(', ', $res['data']['pending_after'] ?? []) ?: 'none')
            : 'Failed: '.($res['data']['error'] ?? $res['error'] ?? $res['raw'] ?? 'unknown');

        return back()->with('sync_out', "Back up & migrate live\n\n".$out);
    }
}
