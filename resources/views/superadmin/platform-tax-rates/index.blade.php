<x-superadmin-layout title="Platform Tax Rates">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Platform Tax Rates</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.platform-tax-rates.create') }}'">New Rate</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        Tax {{ config('app.name') }} itself charges a business for its own subscription/purchases, based on the business's own address (Business Details in their profile). Leave "Province" blank on a row to make it the default rate for any Canadian province not listed elsewhere. Businesses outside Canada are never charged this tax.
    </p>

    @if ($rates->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No platform tax rates configured yet — no business is currently charged platform tax.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($rates as $rate)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $rate->country_code }} &mdash; {{ $rate->province ?? 'Default (any other province)' }}
                            </p>
                            @if ($rate->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Inactive</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $rate->tax_label }} &middot; {{ rtrim(rtrim(number_format($rate->rate, 3), '0'), '.') }}%
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('superadmin.platform-tax-rates.edit', $rate) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Edit</a>
                        <form method="POST" action="{{ route('superadmin.platform-tax-rates.destroy', $rate) }}"
                            onsubmit="return confirm('Delete this platform tax rate?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
