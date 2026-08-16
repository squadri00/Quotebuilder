<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('profile.edit') }}" class="hover:text-gray-700">Business Settings</a> / Tax Rates
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Tax Rates</h2>
            </div>
            <x-primary-button onclick="window.location='{{ route('tax-rates.create') }}'">New Tax Line</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        Add one line per tax you charge — they're applied to every quote's subtotal and shown as separate lines to your customers. Add several for regions with more than one sales tax (e.g. GST + PST), or leave this empty if you don't charge sales tax.
    </p>

    @if ($taxRates->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No tax lines yet — quotes won't include any tax until you add one.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Description</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Rate</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($taxRates as $taxRate)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('tax-rates.edit', $taxRate) }}" class="font-medium brand-text">{{ $taxRate->title }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $taxRate->description ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ rtrim(rtrim(number_format($taxRate->rate, 3), '0'), '.') }}%</td>
                                <td class="px-4 py-3">
                                    @if ($taxRate->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('tax-rates.edit', $taxRate) }}" class="text-sm font-medium brand-text">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-app-layout>
