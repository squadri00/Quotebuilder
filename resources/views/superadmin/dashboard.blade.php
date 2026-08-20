@php
    $maxSignups = max(1, max($signups));
@endphp

<x-superadmin-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Dashboard</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Businesses</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalBusinesses }}</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Active</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $activeBusinesses }}</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Inactive</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $inactiveBusinesses }}</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Quotes (platform-wide)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalQuotes }}</p>
            <a href="{{ route('superadmin.exports.quotes') }}" class="mt-1 inline-block text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Export CSV</a>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <x-card>
            <p class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Signups — last 14 days</p>
            <div class="flex items-end gap-2 h-40">
                @foreach ($signups as $date => $count)
                    <div class="flex-1 flex flex-col items-center justify-end h-full gap-1" title="{{ \Illuminate\Support\Carbon::parse($date)->format('M j') }}: {{ $count }} signup(s)">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $count > 0 ? $count : '' }}</span>
                        <div class="w-full bg-indigo-500 rounded-t"
                            style="height: {{ $count > 0 ? max(4, ($count / $maxSignups) * 100) : 2 }}%; {{ $count === 0 ? 'background-color:#e5e7eb;' : '' }}">
                        </div>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ \Illuminate\Support\Carbon::parse($date)->format('n/j') }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <p class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Quotes per business (top 8)</p>
            @if ($topBusinessesByQuotes->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No businesses yet.</p>
            @else
                <div class="space-y-3">
                    @php $maxQuotes = max(1, $topBusinessesByQuotes->max('quotes_count')); @endphp
                    @foreach ($topBusinessesByQuotes as $business)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-700 dark:text-gray-300">{{ $business->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $business->quotes_count }}</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ max(2, ($business->quotes_count / $maxQuotes) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Financial Overview</h3>
            @if (Route::has('superadmin.financial.index'))
                <a href="{{ route('superadmin.financial.index') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                    View full report &rarr;
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-card>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">MRR</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($mrr, 2) }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $activeSubscriptionCounts['plan'] }} plan &middot; {{ $activeSubscriptionCounts['support'] }} support</p>
            </x-card>

            <x-card>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Revenue This Month</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($revenueThisMonth, 2) }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">tax included</p>
            </x-card>

            <x-card>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tax This Month</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($taxThisMonth, 2) }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">HST/GST held for remittance</p>
            </x-card>

            <x-card class="{{ $pastDue['count'] > 0 ? 'ring-1 ring-amber-400 dark:ring-amber-500' : '' }}">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Past Due</p>
                @if ($pastDue['count'] > 0)
                    <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">${{ number_format($pastDue['mrr_at_risk'], 2) }}/mo</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $pastDue['businesses']->implode(', ') }}">{{ $pastDue['count'] }} sub(s): {{ $pastDue['businesses']->implode(', ') }}</p>
                @else
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">$0.00</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">No failed charges right now</p>
                @endif
            </x-card>
        </div>

        <x-card class="mt-6">
            <p class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Revenue by source — this month</p>
            <div class="space-y-3">
                @php $maxSource = max(1, max($financialBreakdownThisMonth)); @endphp
                @foreach ($financialBreakdownThisMonth as $source => $amount)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 dark:text-gray-300">{{ $source }}</span>
                            <span class="text-gray-500 dark:text-gray-400">${{ number_format($amount, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $amount > 0 ? max(2, ($amount / $maxSource) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</x-superadmin-layout>
