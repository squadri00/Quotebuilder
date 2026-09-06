@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-affiliate-layout title="Commissions" active="commissions">

    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['Pending approval', $totals->pending, ''],
            ['Approved', $totals->approved, 'text-orange-600'],
            ['On a statement', $totals->on_payout, ''],
            ['Paid', $totals->paid, ''],
        ] as [$l, $val, $cls])
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <div class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $l }}</div>
                <div class="mt-1 text-xl font-bold {{ $cls }}">{{ $m($val) }}</div>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" class="mb-3">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                <option value="">All statuses</option>
                @foreach (['pending', 'approved', 'on_payout', 'paid', 'void'] as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </form>
        @if ($rows->isEmpty())
            <p class="text-sm text-slate-400 dark:text-slate-500">Nothing here yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-slate-500 dark:text-slate-400">
                        <th class="py-2">Period</th><th>Business</th><th>Type</th><th>Base</th><th>Rate</th><th>Amount</th><th>Status</th><th>Statement</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($rows as $c)
                        <tr class="border-t border-slate-100 dark:border-slate-800/60">
                            <td class="py-2">{{ sprintf('%04d-%02d', $c->period_year, $c->period_month) }}</td>
                            <td>{{ $c->business?->name ?? $c->business_name_snapshot }}</td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $c->kind }}</td>
                            <td>{{ $c->kind === 'commission' ? $m($c->base_amount) : '—' }}</td>
                            <td>{{ $c->kind === 'commission' ? rtrim(rtrim(number_format((float) $c->rate, 3), '0'), '.') . '%' : '—' }}</td>
                            <td class="font-semibold {{ $c->commission_amount < 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ $m($c->commission_amount) }}</td>
                            <td><x-affiliate-badge :status="$c->status" /></td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $c->payout?->payout_number ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-affiliate-layout>
