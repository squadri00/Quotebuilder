<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Dashboard</h2>
    </x-slot>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        Welcome back, {{ $business->name }}.
        @if ($business->industry)
            <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300 ml-1">{{ $business->industry->name }}</span>
        @endif
    </p>

    @if ($headerAnnouncements->isNotEmpty())
        @php
            $topAnnouncement = $headerAnnouncements->first();
            $sevClasses = match ($topAnnouncement->severity) {
                'critical' => 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950',
                'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950',
                default => 'border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950',
            };
        @endphp
        <x-card class="mb-6 {{ $sevClasses }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $topAnnouncement->title }}</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $topAnnouncement->message }}</p>
                    @if ($headerAnnouncements->count() > 1)
                        <a href="{{ route('announcements.index') }}" class="mt-1 inline-block text-xs font-medium brand-text">
                            + {{ $headerAnnouncements->count() - 1 }} more announcement(s)
                        </a>
                    @endif
                </div>
                <form method="POST" action="{{ route('announcements.dismiss', $topAnnouncement) }}">
                    @csrf
                    <button type="submit" class="shrink-0 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Dismiss</button>
                </form>
            </div>
        </x-card>
    @endif

    @if ($showOnboarding)
        <x-card class="mb-6 border-indigo-200 bg-indigo-50 dark:border-indigo-900 dark:bg-indigo-950">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Finish setting up your account</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $progress['completed'] }} of {{ $progress['total'] }} steps done — a few things are worth finishing before you send your first real quote.</p>
                    <div class="w-full max-w-xs bg-indigo-100 rounded-full h-1.5 mt-2 dark:bg-indigo-900">
                        <div class="h-1.5 rounded-full brand-bg" style="width: {{ $progress['percent'] }}%"></div>
                    </div>
                    <a href="{{ route('onboarding.create') }}" class="mt-2 inline-block text-sm font-medium brand-text">Continue Setup Guide →</a>
                </div>
                <form method="POST" action="{{ route('onboarding.dismiss') }}">
                    @csrf
                    <button type="submit" class="shrink-0 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Dismiss</button>
                </form>
            </div>
        </x-card>
    @endif

    @if ($draftProductCount > 0)
        <x-card class="mb-6 border-amber-200 bg-amber-50">
            <p class="text-sm font-medium text-amber-800">
                {{ $draftProductCount }} product(s) have unpublished changes.
                <a href="{{ route('products.index') }}" class="underline hover:no-underline">Review and publish</a>
            </p>
        </x-card>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Products</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $productCount }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $activeProductCount }} active</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Quotes (all time)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $quoteCount }}</p>
        </x-card>

        @php
            $quoteUsagePercent = $quoteLimit ? min(100, (int) round($quotesThisMonth / max($quoteLimit, 1) * 100)) : null;
            $quoteUsageIsHigh = $quoteUsagePercent !== null && $quoteUsagePercent >= 80;
        @endphp
        <x-card class="{{ $quoteUsageIsHigh ? 'border-amber-300 dark:border-amber-800' : '' }}">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Quotes this month</p>
            <p class="mt-1 text-2xl font-bold {{ $quoteUsageIsHigh ? 'text-amber-700 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">
                {{ $quotesThisMonth }}{{ $quoteLimit !== null ? ' of '.$quoteLimit : '' }}
            </p>
            @if ($quoteLimit === null)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unlimited on your plan</p>
            @else
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                    <div class="h-1.5 rounded-full {{ $quoteUsageIsHigh ? 'bg-amber-500' : 'brand-bg' }}" style="width: {{ $quoteUsagePercent }}%"></div>
                </div>
                <p class="mt-1 text-xs {{ $quoteUsageIsHigh ? 'text-amber-700 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                    @if ($quotesThisMonth >= $quoteLimit)
                        Limit reached — resets {{ $quoteResetDate->format('M j') }}
                    @else
                        Resets {{ $quoteResetDate->format('M j') }}
                    @endif
                </p>
            @endif
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Current Plan</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $business->plan?->name ?? 'None' }}</p>
            <a href="{{ route('billing.index') }}" class="mt-1 inline-block text-xs font-medium brand-text">Manage billing</a>
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Public Quotes (this month)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $publicQuotesThisMonth }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Submitted by customers online</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Public Quotes (all time)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $publicQuotesAllTime }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Submitted by customers online</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Internal Quotes (this month)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $internalQuotesThisMonth }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Created by your team</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Internal Quotes (all time)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $internalQuotesAllTime }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Created by your team</p>
        </x-card>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <x-primary-button onclick="window.location='{{ route('products.create') }}'">New Product</x-primary-button>
        <x-secondary-button onclick="window.location='{{ route('rules.index') }}'">View Rules</x-secondary-button>
        @if ($recentQuotes !== null)
            <x-secondary-button onclick="window.location='{{ route('quotes.index') }}'">View All Quotes</x-secondary-button>
        @endif
    </div>

    @if ($recentQuotes !== null)
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Recent Quotes</h3>
                <a href="{{ route('quotes.index') }}" class="text-sm font-medium brand-text">View all</a>
            </div>

            @if ($recentQuotes->isEmpty())
                <x-card class="text-center text-gray-500 dark:text-gray-400">
                    No quotes yet.
                </x-card>
            @else
                <x-card class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Price</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recentQuotes as $quote)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $quote->customer_name }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $quote->product?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">${{ number_format($quote->final_price, 2) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $quote->created_at->format('M j, Y g:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        </div>
    @endif
</x-app-layout>
