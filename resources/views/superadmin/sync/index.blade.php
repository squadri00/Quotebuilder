@php
    use Illuminate\Support\Str;

    $sev = fn ($s) => [
        'critical' => 'border-red-500 bg-red-50 dark:bg-red-950/30',
        'warn'     => 'border-amber-500 bg-amber-50 dark:bg-amber-950/30',
        'info'     => 'border-slate-400 bg-slate-50 dark:bg-slate-800/40',
    ][$s] ?? 'border-slate-300';
    $badge = fn ($s) => [
        'critical' => 'bg-red-600 text-white',
        'warn'     => 'bg-amber-500 text-white',
        'info'     => 'bg-slate-500 text-white',
    ][$s] ?? 'bg-slate-400 text-white';

    $lg = $local['git'] ?? [];
    $rg = $live['git'] ?? [];

    $localApplied = collect($local['db']['applied_migrations'] ?? [])->pluck('filename')->all();
    $liveApplied  = collect(($live['db']['applied_migrations'] ?? []))->pluck('filename')->all();
    $livePending  = collect(($live['db']['pending_migrations'] ?? []))->pluck('filename')->all();
    $missingOnLive = array_values(array_diff($localApplied, $liveApplied));
    $toApply = array_values(array_unique(array_merge($missingOnLive, $livePending)));
    $ledgerMissingLive = $live && empty($live['db']['ledger_installed']);
    $canApply = $isComparator && ! $liveError && $live && ($toApply || $ledgerMissingLive);
    $allMig = collect(array_merge($localApplied, $liveApplied, $livePending,
        collect($local['db']['pending_migrations'] ?? [])->pluck('filename')->all()))->unique()->sort()->values();
@endphp

<x-superadmin-layout title="Environment Sync">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Environment Sync</h2>
            <a href="{{ route('superadmin.sync.index', ['fresh' => 1]) }}"
               class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">↻ Check now</a>
        </div>
    </x-slot>

    @if (session('sync_out'))
        <x-card class="mb-4">
            <pre class="overflow-x-auto whitespace-pre-wrap rounded bg-slate-900 p-3 text-xs text-slate-100">{{ trim(session('sync_out')) }}</pre>
            <a href="{{ route('superadmin.sync.index', ['fresh' => 1]) }}" class="mt-2 inline-block text-sm text-indigo-600">Re-check</a>
        </x-card>
    @endif

    @if (! $configured)
        <x-card>
            <h3 class="font-semibold">Not configured yet</h3>
            <p class="mt-1 text-sm text-slate-500">
                Set <code>SYNC_SHARED_SECRET</code> in <code>.env</code> on <strong>both</strong> this machine and the
                live server (same value). On this machine only, also set <code>SYNC_LIVE_ENDPOINT</code> and
                <code>SYNC_LIVE_APPLY_ENDPOINT</code> to the live URLs. Generate a secret:
                <code>php -r "echo bin2hex(random_bytes(32));"</code>. Then run
                <code>php artisan config:clear</code> and reload.
            </p>
        </x-card>
    @else

    {{-- Verdict --}}
    @php
        $vClass = 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
        $vText  = 'In sync';
        if ($isComparator && $liveError) {
            $vClass = 'bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-300';
            $vText  = 'Could not reach the live environment: '.$liveError;
        } elseif ($isComparator && $diff) {
            if ($diff['ok']) {
                $n = (int) ($diff['counts']['info'] ?? 0);
                $vText = 'In sync — code, schema and files match.'.($n ? " ($n FYI note".Str::plural('', $n)." below — config data, report-only.)" : '');
            } else {
                $vClass = 'bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-300';
                $vText  = "Out of sync — {$diff['counts']['critical']} critical, {$diff['counts']['warn']} warning".Str::plural('', $diff['counts']['warn']).'.';
            }
        } elseif (! $isComparator) {
            if ($selfIssues) {
                $vClass = 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300';
                $vText  = count($selfIssues).' thing(s) to deal with on this server (self-check — not comparing against the other environment).';
            } else {
                $vText = 'Server checks OK — committed, '
                    .($selfRemoteChecked ? 'on the latest commit' : 'remote not reachable to verify a pending pull')
                    .', no un-run migrations. This box is not configured to compare against the other environment.';
            }
        }
    @endphp
    <div class="mb-4 rounded-xl px-4 py-3 text-sm font-semibold {{ $vClass }}">{{ $vText }}</div>

    @if ($liveCacheAge !== null)
        <p class="-mt-2 mb-4 text-xs text-slate-500">
            ⏱ Live snapshot is {{ $liveCacheAge < 90 ? $liveCacheAge.'s' : round($liveCacheAge/60).' min' }} old
            (from the live server's cache). <a class="text-indigo-600" href="{{ route('superadmin.sync.index', ['fresh' => 1]) }}">Check now</a> forces a fresh read.
        </p>
    @endif

    {{-- 3-node picture --}}
    @if ($isComparator && ! $liveError && $live)
        @php
            $link1 = empty($lg['dirty']) && (! $trueOrigin || $trueOrigin === ($lg['head'] ?? null));
            $link2 = ! $trueOrigin || $trueOrigin === ($rg['head'] ?? null);
        @endphp
        <div class="mb-6 flex flex-wrap items-center gap-2 text-xs">
            <div class="min-w-[150px] rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="uppercase tracking-wide text-slate-400">This machine</div>
                <div class="mt-0.5 font-mono">{{ $lg['head_short'] ?? '?' }}{{ ! empty($lg['dirty']) ? ' +'.$lg['dirty_count'].' uncommitted' : '' }}</div>
            </div>
            <span class="text-lg {{ $link1 ? 'text-emerald-600' : 'text-red-600' }}">{{ $link1 ? '→' : '⇢✗' }}</span>
            <div class="min-w-[150px] rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="uppercase tracking-wide text-slate-400">Remote (origin/{{ $localBranch }})</div>
                <div class="mt-0.5 font-mono">{{ $trueOrigin ? substr($trueOrigin, 0, 10) : 'unknown' }}</div>
            </div>
            <span class="text-lg {{ $link2 ? 'text-emerald-600' : 'text-red-600' }}">{{ $link2 ? '→' : '⇢✗' }}</span>
            <div class="min-w-[150px] rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="uppercase tracking-wide text-slate-400">Live ({{ $live['host'] ?? 'live' }})</div>
                <div class="mt-0.5 font-mono">{{ $rg['head_short'] ?? '?' }}</div>
            </div>
        </div>
    @endif

    {{-- Code & files --}}
    <x-card class="mb-4">
        <div class="flex items-center justify-between">
            <strong class="text-sm">⌥ Code &amp; files</strong>
            <div class="flex gap-2">
                @if ($isComparator && (! empty($lg['ahead_origin']) || ! empty($lg['dirty'])))
                    <form method="POST" action="{{ route('superadmin.sync.push') }}"
                          onsubmit="return confirm('Run git push now? (Commit first if you have uncommitted changes.)')">
                        @csrf
                        <button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">☁ Push now</button>
                    </form>
                @endif
                @if ($isComparator && ! $liveError)
                    <a href="{{ route('superadmin.sync.index', ['view' => 'files']) }}"
                       class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs dark:border-slate-600">Compare files</a>
                @endif
            </div>
        </div>
        <p class="mt-2 text-sm text-slate-500">
            @if (! empty($lg['dirty']))
                {{ $lg['dirty_count'] }} uncommitted file(s) on this machine — commit them, then push.
            @elseif (! empty($lg['ahead_origin']))
                {{ $lg['ahead_origin'] }} commit(s) not on the remote yet — click <strong>Push now</strong>.
            @elseif ($isComparator && ! $liveError && ($local['files']['manifest_hash'] ?? 1) !== ($live['files']['manifest_hash'] ?? 2))
                Files differ between the two environments — click <strong>Compare files</strong>.
            @elseif ($isComparator && ! $liveError)
                Code is committed, pushed, and matches live.
            @else
                Commit &amp; push status for this machine.
            @endif
        </p>
    </x-card>

    {{-- Database migrations --}}
    <x-card class="mb-4">
        <div class="flex items-center justify-between">
            <strong class="text-sm">▤ Database migrations</strong>
            @if ($isComparator && ! $liveError)
                <form method="POST" action="{{ route('superadmin.sync.apply-live') }}"
                      onsubmit="return confirm('This BACKS UP the live database, then runs {{ count($toApply) ?: 1 }} migration(s) on it. Continue?')">
                    @csrf
                    <button @disabled(! $canApply)
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $canApply ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 dark:bg-slate-700' }}">
                        {{ $canApply ? '⤓ Back up & migrate live' : 'Live database is up to date' }}
                    </button>
                </form>
            @endif
        </div>
        <div class="mt-2 text-sm">
            @if (! $isComparator)
                <p class="text-slate-500">Self-check mode. Pending on <em>this</em> machine:
                    {{ $local['db']['pending_migrations'] ? implode(', ', collect($local['db']['pending_migrations'])->pluck('filename')->all()) : 'none' }}.
                    Run <code>php artisan migrate</code>.</p>
            @elseif ($liveError)
                <p class="text-slate-500">Couldn't reach the live environment, so migration state can't be compared.</p>
            @else
                <p class="mt-0">
                    @if ($ledgerMissingLive)
                        <span class="font-semibold text-red-600">The migrations table doesn't exist on live.</span>
                        The button installs it and runs everything.
                    @elseif ($toApply)
                        <span class="font-semibold text-red-600">{{ count($toApply) }} migration(s) run here but not on live.</span>
                        <strong>Back up &amp; migrate live</strong> takes a full gzipped backup first (keeps 10), aborts if the backup fails.
                    @else
                        <span class="font-semibold text-emerald-600">Live database is up to date</span> — same migrations on both.
                    @endif
                </p>
                @if ($allMig->count())
                    <table class="mt-2 w-full text-xs">
                        <thead><tr class="text-left text-slate-400">
                            <th class="py-1">Migration</th><th class="text-center">This machine</th><th class="text-center">Live</th>
                        </tr></thead>
                        <tbody>
                        @foreach ($allMig as $mf)
                            <tr class="border-t border-slate-100 dark:border-slate-800">
                                <td class="py-1 font-mono">{{ $mf }}</td>
                                <td class="text-center">{{ in_array($mf, $localApplied, true) ? '✓' : '—' }}</td>
                                <td class="text-center {{ in_array($mf, $liveApplied, true) ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ in_array($mf, $liveApplied, true) ? '✓' : 'missing' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            @endif
        </div>
    </x-card>

    {{-- Issue list --}}
    @php $issues = $isComparator && $diff ? $diff['issues'] : array_map(fn ($i) => $i + ['area' => '', 'action' => null, 'detail' => null], $selfIssues); @endphp
    @foreach ($issues as $i)
        <div class="mb-2 rounded-lg border border-l-4 p-3 {{ $sev($i['sev']) }}">
            <div class="flex items-center gap-2">
                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $badge($i['sev']) }}">{{ $i['sev'] }}</span>
                @if (! empty($i['area']))<span class="text-[11px] uppercase text-slate-400">{{ $i['area'] }}</span>@endif
            </div>
            <div class="mt-1 text-sm font-semibold">{{ $i['msg'] }}</div>
            @if (! empty($i['action']))<div class="mt-1 text-xs text-slate-500">→ {{ $i['action'] }}</div>@endif
            @if (! empty($i['detail']) && is_array($i['detail']))
                @if (($i['area'] ?? '') === 'config')
                    <div class="mt-1 text-xs">
                        <a class="text-indigo-600" href="{{ route('superadmin.sync.index', ['view' => 'config', 'table' => $i['detail']['table']]) }}">See row differences →</a>
                        ({{ (int) ($i['detail']['rows_local'] ?? 0) }} here / {{ (int) ($i['detail']['rows_live'] ?? 0) }} live)
                    </div>
                @elseif (isset($i['detail'][0]) && is_string($i['detail'][0]))
                    <ul class="mt-1 list-disc pl-5 text-xs text-slate-500">
                        @foreach (array_slice($i['detail'], 0, 20) as $d)<li class="font-mono">{{ $d }}</li>@endforeach
                        @if (count($i['detail']) > 20)<li>… +{{ count($i['detail']) - 20 }} more</li>@endif
                    </ul>
                @else
                    <ul class="mt-1 list-disc pl-5 text-xs text-slate-500">
                        @foreach ($i['detail'] as $k => $v)<li>{{ $k }}: <code>{{ is_scalar($v) ? $v : json_encode($v) }}</code></li>@endforeach
                    </ul>
                @endif
            @endif
        </div>
    @endforeach

    {{-- On-demand: file differences --}}
    @if ($fileDiff)
        <x-card class="mt-4">
            <strong class="text-sm">File differences</strong>
            @if (! $fileDiff['ok'])
                <div class="mt-2 rounded bg-red-50 p-2 text-sm text-red-700">Could not fetch the live manifest: {{ $fileDiff['error'] }}</div>
            @elseif (! $fileDiff['only_local'] && ! $fileDiff['only_live'] && ! $fileDiff['changed'])
                <p class="mt-2 text-sm text-slate-500">Every tracked file matches.</p>
            @else
                @if ($fileDiff['changed'])
                    <h4 class="mt-2 text-sm font-semibold">Different content ({{ count($fileDiff['changed']) }})</h4>
                    <table class="w-full text-xs"><tbody>
                        @foreach ($fileDiff['changed'] as $c)
                            <tr class="border-t border-slate-100 dark:border-slate-800"><td class="py-1 font-mono">{{ $c['path'] }}</td>
                                <td class="text-right">{{ $c['local_size'] }} / {{ $c['live_size'] }}</td></tr>
                        @endforeach
                    </tbody></table>
                @endif
                @if ($fileDiff['only_local'])
                    <h4 class="mt-2 text-sm font-semibold">Here but NOT on live ({{ count($fileDiff['only_local']) }})</h4>
                    <ul class="list-disc pl-5 text-xs font-mono">@foreach ($fileDiff['only_local'] as $p)<li>{{ $p }}</li>@endforeach</ul>
                @endif
                @if ($fileDiff['only_live'])
                    <h4 class="mt-2 text-sm font-semibold">On live but NOT here ({{ count($fileDiff['only_live']) }})</h4>
                    <ul class="list-disc pl-5 text-xs font-mono">@foreach ($fileDiff['only_live'] as $p)<li>{{ $p }}</li>@endforeach</ul>
                @endif
            @endif
            <a href="{{ route('superadmin.sync.index') }}" class="mt-2 inline-block text-sm text-indigo-600">Close</a>
        </x-card>
    @endif

    {{-- On-demand: config row diff --}}
    @if ($configDiff)
        <x-card class="mt-4">
            <strong class="text-sm">Config table: <code>{{ $configDiff['table'] }}</code></strong>
            @if (! $configDiff['ok'])
                <div class="mt-2 rounded bg-red-50 p-2 text-sm text-red-700">Could not fetch live rows: {{ $configDiff['error'] }}</div>
            @elseif (! $configDiff['only_local'] && ! $configDiff['only_live'] && ! $configDiff['changed'])
                <p class="mt-2 text-sm text-slate-500">Rows match.</p>
            @else
                @if ($configDiff['only_local'])
                    <h4 class="mt-2 text-sm font-semibold">Rows here but not live ({{ count($configDiff['only_local']) }})</h4>
                    <pre class="overflow-x-auto rounded bg-slate-900 p-2 text-xs text-slate-100">{{ json_encode($configDiff['only_local'], JSON_PRETTY_PRINT) }}</pre>
                @endif
                @if ($configDiff['only_live'])
                    <h4 class="mt-2 text-sm font-semibold">Rows live but not here ({{ count($configDiff['only_live']) }})</h4>
                    <pre class="overflow-x-auto rounded bg-slate-900 p-2 text-xs text-slate-100">{{ json_encode($configDiff['only_live'], JSON_PRETTY_PRINT) }}</pre>
                @endif
                @if ($configDiff['changed'])
                    <h4 class="mt-2 text-sm font-semibold">Rows that differ ({{ count($configDiff['changed']) }})</h4>
                    @foreach ($configDiff['changed'] as $k => $pair)
                        <div class="mb-2"><strong class="text-xs">{{ $k }}</strong>
                            <pre class="overflow-x-auto rounded bg-slate-900 p-2 text-xs text-slate-100">LOCAL: {{ json_encode($pair[0]) }}
LIVE:  {{ json_encode($pair[1]) }}</pre></div>
                    @endforeach
                @endif
                <p class="mt-1 text-xs text-slate-500">Config-table drift is report-only — apply intended rows by hand.</p>
            @endif
            <a href="{{ route('superadmin.sync.index') }}" class="mt-2 inline-block text-sm text-indigo-600">Close</a>
        </x-card>
    @endif

    {{-- Raw fingerprints --}}
    <details class="mt-4">
        <summary class="cursor-pointer text-sm text-slate-500">Raw fingerprints</summary>
        <div class="mt-2 grid gap-3 md:grid-cols-2">
            <div><strong class="text-xs">This machine</strong>
                <pre class="mt-1 max-h-96 overflow-auto rounded bg-slate-900 p-2 text-[11px] text-slate-100">{{ json_encode($local, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
            <div><strong class="text-xs">Live</strong>
                <pre class="mt-1 max-h-96 overflow-auto rounded bg-slate-900 p-2 text-[11px] text-slate-100">{{ $live ? json_encode($live, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ($liveError ?: 'not fetched') }}</pre></div>
        </div>
    </details>
    @endif
</x-superadmin-layout>
