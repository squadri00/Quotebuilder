@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-affiliate-layout title="Dashboard" active="dashboard">

    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['Link clicks', $stats['clicks']],
            ['Sign-ups', $stats['signups'], $stats['conversion_rate'] . '% conversion'],
            ['Active businesses', $stats['active_referrals']],
            ['Referred MRR', $m($stats['referred_mrr'])],
            ['Commission this month', $m($stats['commission_month'])],
        ] as $tile)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-[11px] uppercase tracking-wide text-slate-400">{{ $tile[0] }}</div>
                <div class="mt-1 text-2xl font-bold">{{ $tile[1] }}</div>
                @isset($tile[2])<div class="text-xs text-slate-400">{{ $tile[2] }}</div>@endisset
            </div>
        @endforeach
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-[11px] uppercase tracking-wide text-slate-400">Approved &amp; unpaid</div>
            <div class="mt-1 text-2xl font-bold text-orange-600">{{ $m($stats['commission_unpaid']) }}</div>
            <div class="text-xs text-slate-400">{{ $m($stats['commission_pending']) }} pending approval</div>
        </div>
    </div>

    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Your referral link</h2>
        <div class="flex flex-wrap items-center gap-2 rounded-lg bg-slate-100 p-3 font-mono text-sm break-all">
            <span id="reflink">{{ $partner->referralUrl() }}</span>
            <button type="button" class="rounded-md bg-slate-800 px-3 py-1 text-xs font-semibold text-white"
                    onclick="navigator.clipboard.writeText(document.getElementById('reflink').textContent);this.textContent='Copied'">Copy</button>
        </div>
        <p class="mt-2 text-xs text-slate-500">
            A signup counts for you for {{ $cookieDays }} days after someone clicks. You earn
            {{ rtrim(rtrim(number_format((float) $partner->commission_rate, 3), '0'), '.') }}% of every payment
            those businesses make, for the life of the account.
        </p>
    </div>

    @if ($eligible->isNotEmpty())
        <div class="mb-5 rounded-xl border border-orange-300 bg-white p-5">
            <h2 class="mb-2 text-sm font-semibold">Statement ready</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-500"><th class="py-1">Month</th><th>Lines</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                @foreach ($eligible as $mn)
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ \Carbon\Carbon::create($mn->period_year, $mn->period_month, 1)->format('F Y') }}</td>
                        <td>{{ $mn->line_count }}</td>
                        <td class="font-semibold">{{ $m($mn->amount_total) }}</td>
                        <td class="text-right">
                            <a href="{{ route('affiliate.portal.statements', ['y' => $mn->period_year, 'm' => $mn->period_month]) }}"
                               class="rounded-md bg-orange-600 px-3 py-1 text-xs font-semibold text-white">Generate</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold">Recent referrals</h2>
            @forelse ($recentReferrals as $r)
                @if ($loop->first)
                    <table class="w-full text-sm"><thead><tr class="text-left text-slate-500"><th class="py-1">Business</th><th>Plan</th><th>Status</th></tr></thead><tbody>
                @endif
                <tr class="border-t border-slate-100">
                    <td class="py-2">{{ $r->business?->name }}</td>
                    <td class="text-slate-500">{{ $r->business?->plan?->name ?? '—' }}</td>
                    <td><x-affiliate-badge :status="$r->status" /></td>
                </tr>
                @if ($loop->last)</tbody></table>
                    <a href="{{ route('affiliate.portal.referrals') }}" class="mt-2 inline-block text-xs text-slate-500 hover:underline">View all →</a>
                @endif
            @empty
                <p class="text-sm text-slate-400">No referrals yet — share your link to get started.</p>
            @endforelse
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold">Recent commission</h2>
            @forelse ($recentCommissions as $c)
                @if ($loop->first)
                    <table class="w-full text-sm"><thead><tr class="text-left text-slate-500"><th class="py-1">Period</th><th>Business</th><th>Amount</th><th>Status</th></tr></thead><tbody>
                @endif
                <tr class="border-t border-slate-100">
                    <td class="py-2">{{ sprintf('%04d-%02d', $c->period_year, $c->period_month) }}</td>
                    <td class="text-slate-500">{{ $c->business?->name ?? $c->business_name_snapshot }}</td>
                    <td class="font-semibold">{{ $m($c->commission_amount) }}</td>
                    <td><x-affiliate-badge :status="$c->status" /></td>
                </tr>
                @if ($loop->last)</tbody></table>
                    <a href="{{ route('affiliate.portal.commissions') }}" class="mt-2 inline-block text-xs text-slate-500 hover:underline">View ledger →</a>
                @endif
            @empty
                <p class="text-sm text-slate-400">Nothing accrued yet.</p>
            @endforelse
        </div>
    </div>
</x-affiliate-layout>
