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
        <x-card>
            <p class="font-semibold text-gray-900 dark:text-gray-100">Billing</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Billing and subscription management isn't set up yet. This is a placeholder for where plan tiers, invoices, and payment status will live once billing is built.
            </p>
        </x-card>
    </div>
</x-superadmin-layout>
