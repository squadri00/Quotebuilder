@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-affiliate-layout title="Earnings estimator" active="estimator">
    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-1 text-sm font-semibold">Estimate your earnings</h2>
        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">Rough projection only — actual commission follows real payments and your approved referrals.</p>
        <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium">Plan price / month ($)</label>
                <input name="price" type="number" step="0.01" value="{{ $inputs['price'] ?: '' }}" required
                       class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                @if ($plans->isNotEmpty())
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        @foreach ($plans as $pl)
                            <a href="#" onclick="this.closest('form').price.value={{ (float) $pl->price }};return false" class="mr-2 text-orange-600">{{ $pl->name }} ${{ number_format((float) $pl->price, 2) }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div><label class="mb-1 block text-xs font-medium">Your commission rate (%)</label>
                <input name="rate" type="number" step="0.001" value="{{ $inputs['rate'] }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm"></div>
            <div><label class="mb-1 block text-xs font-medium">Businesses referred</label>
                <input name="count" type="number" value="{{ $inputs['count'] }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm"></div>
            <div><label class="mb-1 block text-xs font-medium">Avg. months each stays</label>
                <input name="months" type="number" value="{{ $inputs['months'] }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm"></div>
            <div><label class="mb-1 block text-xs font-medium">New sign-ups / month</label>
                <input name="new_per_month" type="number" value="{{ $inputs['newPerMonth'] }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm"></div>
            <div class="self-end"><button class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Calculate</button></div>
        </form>
    </div>

    @if ($inputs['price'] > 0)
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['Per account / month', $m($results['per_account_monthly']), ''],
                ['Per account (lifetime)', $m($results['per_account_total']), $inputs['months'] . ' months'],
                [$inputs['count'] . ' accounts (lifetime)', $m($results['cohort_total']), 'text-orange-600'],
                ['Year-1 at ' . $inputs['newPerMonth'] . '/mo', $m($results['year1']), ''],
            ] as [$l, $val, $sub])
                <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $l }}</div>
                    <div class="mt-1 text-xl font-bold {{ str_starts_with($sub, 'text-') ? $sub : '' }}">{{ $val }}</div>
                    @if ($sub && ! str_starts_with($sub, 'text-'))<div class="text-xs text-slate-400 dark:text-slate-500">{{ $sub }}</div>@endif
                </div>
            @endforeach
        </div>
    @endif
</x-affiliate-layout>
