<x-superadmin-layout title="Demo Quote">
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Testing / Demo — never a real business</p>
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Quote Created</h2>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="max-w-lg">
        <x-card>
            <div class="text-center">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    Demo Quote
                </span>

                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ $product->name }} — {{ $quote->customer_name }} ({{ $quote->customer_email }})</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Quoted as: {{ $quote->demoBusinessOverride()['name'] ?? $business->name }}
                    @if ($quote->demoBusinessOverride()['address'] ?? null)
                        — {{ $quote->demoBusinessOverride()['address'] }}
                    @endif
                    @if ($quote->demoBusinessOverride()['phone'] ?? null)
                        — {{ $quote->demoBusinessOverride()['phone'] }}
                    @endif
                    @if ($quote->demoBusinessOverride()['email'] ?? null)
                        — {{ $quote->demoBusinessOverride()['email'] }}
                    @endif
                </p>

                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">Quoted Price</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($quote->final_price, 2) }}</p>

                @if ($quote->hasPriceOverride())
                    <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                        Overridden — system-calculated price was ${{ number_format($quote->calculated_price, 2) }}
                    </p>
                @endif

                @if (count($quote->meta['applied_rules'] ?? []))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Adjustments</p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            @foreach ($quote->meta['applied_rules'] as $applied)
                                <li class="flex justify-between gap-4">
                                    <span>{{ $applied['name'] }}</span>
                                    <span class="shrink-0">
                                        {{ $applied['amount_changed'] >= 0 ? '+' : '-' }}${{ number_format(abs($applied['amount_changed']), 2) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! empty($quote->meta['discount']))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <ul class="space-y-1 text-sm">
                            <li class="flex justify-between gap-4 text-gray-600 dark:text-gray-300">
                                <span>Discount ({{ $quote->meta['discount']['type'] === 'percentage' ? rtrim(rtrim(number_format($quote->meta['discount']['value'], 2), '0'), '.').'% off' : '$'.number_format($quote->meta['discount']['value'], 2).' off' }})</span>
                                <span class="shrink-0">&minus;${{ number_format($quote->meta['discount']['amount'], 2) }}</span>
                            </li>
                        </ul>
                    </div>
                @endif

                @if (count($quote->meta['tax_lines'] ?? []))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <ul class="space-y-1 text-sm">
                            <li class="flex justify-between gap-4 text-gray-600 dark:text-gray-300">
                                <span>Subtotal</span>
                                <span class="shrink-0">${{ number_format($quote->meta['subtotal_before_tax'], 2) }}</span>
                            </li>
                            @foreach ($quote->meta['tax_lines'] as $taxLine)
                                <li class="flex justify-between gap-4 text-gray-500 dark:text-gray-400">
                                    <span>{{ $taxLine['title'] }} ({{ rtrim(rtrim(number_format($taxLine['rate'], 3), '0'), '.') }}%)</span>
                                    <span class="shrink-0">${{ number_format($taxLine['amount'], 2) }}</span>
                                </li>
                            @endforeach
                            <li class="flex justify-between gap-4 font-semibold text-gray-900 dark:text-gray-100 border-t border-gray-100 dark:border-gray-700 pt-1 mt-1">
                                <span>Total (calculated)</span>
                                <span class="shrink-0">${{ number_format($quote->calculated_price, 2) }}</span>
                            </li>
                        </ul>
                    </div>
                @endif

                @if ($quote->internal_notes)
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Internal Notes</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $quote->internal_notes }}</p>
                    </div>
                @endif

                @if ($quote->prepared_by_name || $quote->expires_at)
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                        @if ($quote->prepared_by_name)
                            <p>Prepared by {{ $quote->prepared_by_name }} ({{ $quote->prepared_by_email }})</p>
                        @endif
                        @if ($quote->expires_at)
                            <p class="{{ $quote->isExpired() ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">
                                {{ $quote->isExpired() ? 'Expired' : 'Valid until' }} {{ $quote->expires_at->format('M j, Y') }}
                            </p>
                        @endif
                    </div>
                @endif

                @if ($quote->emailed_at)
                    <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">
                        Emailed to the customer {{ $quote->emailed_at->diffForHumans() }}.
                    </p>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <form method="POST" action="{{ route('superadmin.quotes.email', $quote) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                            {{ $quote->emailed_at ? 'Resend Email' : 'Email to Customer' }}
                        </button>
                    </form>

                    <a href="{{ route('quote.pdf', $quote) }}" class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download PDF
                    </a>
                </div>

                <div class="mt-4 flex items-center justify-center gap-4 text-sm">
                    <a href="{{ route('superadmin.quotes.create.show', $product) }}" class="font-medium text-indigo-600 dark:text-indigo-400">Create another quote</a>
                    <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                    <a href="{{ route('superadmin.quotes.create') }}" class="font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Back to Demo Quotes</a>
                </div>
            </div>
        </x-card>
    </div>
</x-superadmin-layout>
