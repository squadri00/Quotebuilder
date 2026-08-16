<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tax Rates</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Add one line per tax you charge — they're applied to every quote's subtotal and shown as separate lines to your customers.
        </p>
    </header>

    <div class="mt-4 flex items-center justify-between">
        @if ($taxRateCount === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">No tax lines yet — quotes won't include any tax until you add one.</p>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $taxRateCount }} tax {{ Str::plural('line', $taxRateCount) }} configured.
            </p>
        @endif

        <a href="{{ route('tax-rates.index') }}" class="shrink-0">
            <x-secondary-button type="button">Manage Tax Rates</x-secondary-button>
        </a>
    </div>
</section>
