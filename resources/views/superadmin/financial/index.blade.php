<x-superadmin-layout title="Financial Activity">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Financial Activity</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Monthly Recurring Revenue</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($mrr, 2) }}</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Active + trialing plans &amp; support, right now</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Revenue This Month</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($revenueThisMonth, 2) }}</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Collected, tax included</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tax Collected This Month</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($taxThisMonth, 2) }}</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">HST/GST held for remittance</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Revenue All Time</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($revenueAllTime, 2) }}</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">${{ number_format($taxAllTime, 2) }} tax all time</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Active Subscriptions</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $activeSubscriptionCounts['plan'] + $activeSubscriptionCounts['support'] }}</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $activeSubscriptionCounts['plan'] }} plan &middot; {{ $activeSubscriptionCounts['support'] }} Priority Support</p>
        </x-card>

        <x-card class="{{ $pastDue['count'] > 0 ? 'ring-1 ring-amber-400 dark:ring-amber-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Past Due — Revenue at Risk</p>
            @if ($pastDue['count'] > 0)
                <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">${{ number_format($pastDue['mrr_at_risk'], 2) }}/mo</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $pastDue['count'] }} subscription(s) with a failed charge: {{ $pastDue['businesses']->implode(', ') }}</p>
            @else
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">$0.00</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">No failed charges right now</p>
            @endif
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <x-card>
            <p class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Revenue by source</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr>
                            <th class="py-2 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                            <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">This Month</th>
                            <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">All Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($breakdown as $label => $amounts)
                            <tr>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $label }}</td>
                                <td class="py-2 text-right text-gray-900 dark:text-gray-100">${{ number_format($amounts['this_month'], 2) }}</td>
                                <td class="py-2 text-right text-gray-900 dark:text-gray-100">${{ number_format($amounts['all_time'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card>
            <p class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent activity</p>
            @if ($recentActivity->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No revenue recorded yet.</p>
            @else
                <div class="space-y-3">
                    @foreach ($recentActivity as $entry)
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <p class="text-gray-900 dark:text-gray-100">{{ $entry['business'] }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $entry['label'] }} &middot; {{ \Illuminate\Support\Carbon::parse($entry['date'])->format('M j, Y') }}</p>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-gray-100">${{ number_format($entry['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-superadmin-layout>
