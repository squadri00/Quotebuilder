<!DOCTYPE html>
<html lang="en" class="{{ request('theme') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Your Quote — {{ $product->name }} — {{ $quote->demoBusinessOverride()['name'] ?? $business->name }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>:root { --brand-color: {{ $business->brand_color ?? '#4f46e5' }}; }</style>
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        @php
            $demoBusiness = $quote->demoBusinessOverride();
            $displayBusinessName = $demoBusiness['name'] ?? $business->name;
            // A demo quote (Super Admin testing a template) shows only
            // whatever was typed into that one quote's Demo Business
            // Details. Every ordinary quote falls back to the business's
            // actual saved address/phone/reply-to email — see the PDF
            // template's identical fallback for why.
            $businessAddressLines = $demoBusiness
                ? array_filter([$demoBusiness['address'] ?? null])
                : $business->addressLines();
            $businessPhone = $demoBusiness['phone'] ?? $business->phone;
            $businessEmail = $demoBusiness['email'] ?? $business->notification_email;
            $customerContact = $quote->customerContact();
        @endphp
        <div class="min-h-[600px] flex items-center justify-center px-4 py-12">
            <div class="max-w-md w-full bg-white border border-gray-200 rounded-xl shadow-sm p-8 text-center dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $displayBusinessName }}</p>
                @foreach ($businessAddressLines as $line)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $line }}</p>
                @endforeach
                @if ($businessPhone || $businessEmail)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                        {{ implode(' · ', array_filter([$businessPhone, $businessEmail])) }}
                    </p>
                @endif
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">{{ $product->name }}</p>

                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">Your estimated price</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($quote->final_price, 2) }}</p>

                @if (count($selections))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Selections</p>
                        <ul class="space-y-1 text-sm">
                            @foreach ($selections as $item)
                                <li class="flex justify-between gap-4">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $item['question'] }}</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100 text-right">{{ $item['answer'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (count($result['applied_rules']))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Adjustments</p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            @foreach ($result['applied_rules'] as $applied)
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

                @if (! empty($tax) && count($tax['tax_lines']))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <ul class="space-y-1 text-sm">
                            <li class="flex justify-between gap-4 text-gray-600 dark:text-gray-300">
                                <span>Subtotal</span>
                                <span class="shrink-0">${{ number_format($tax['subtotal'], 2) }}</span>
                            </li>
                            @foreach ($tax['tax_lines'] as $taxLine)
                                <li class="flex justify-between gap-4 text-gray-500 dark:text-gray-400">
                                    <span>{{ $taxLine['title'] }} ({{ rtrim(rtrim(number_format($taxLine['rate'], 3), '0'), '.') }}%)</span>
                                    <span class="shrink-0">${{ number_format($taxLine['amount'], 2) }}</span>
                                </li>
                            @endforeach
                            <li class="flex justify-between gap-4 font-semibold text-gray-900 dark:text-gray-100 border-t border-gray-100 dark:border-gray-700 pt-1 mt-1">
                                <span>Total</span>
                                <span class="shrink-0">${{ number_format($tax['total'], 2) }}</span>
                            </li>
                        </ul>
                    </div>
                @endif

                <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    @if (! empty($revisiting))
                        <p>This quote was for {{ $quote->customer_name }} ({{ $quote->customer_email }}){{ ! empty($customerContact['phone']) ? ' · '.$customerContact['phone'] : '' }}.</p>
                    @else
                        <p>Thanks, {{ $quote->customer_name }} — we've saved this quote for {{ $quote->customer_email }}.</p>
                    @endif

                    @if ($quote->prepared_by_name)
                        <p class="mt-1 text-xs">Prepared by {{ $quote->prepared_by_name }} ({{ $quote->prepared_by_email }})</p>
                    @endif

                    @if ($quote->expires_at)
                        <p class="mt-1 text-xs {{ $quote->isExpired() ? 'text-red-500 dark:text-red-400 font-medium' : '' }}">
                            {{ $quote->isExpired() ? 'Expired' : 'Valid until' }} {{ $quote->expires_at->format('M j, Y') }}
                        </p>
                    @endif
                </div>

                @if (! empty($pdfEnabled))
                    <a href="{{ route('quote.pdf', $quote) }}" class="mt-4 inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download PDF
                    </a>
                @endif

                @if (! empty($viewUrl) && empty($revisiting))
                    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                        Bookmark this link to view your quote again later:<br>
                        <a href="{{ $viewUrl }}" class="brand-text break-all">{{ $viewUrl }}</a>
                    </p>
                @endif

                <a href="{{ route('quote.show', [$business, $product]) }}{{ request('theme') === 'dark' ? '?theme=dark' : '' }}" class="mt-6 inline-block text-sm font-medium brand-text">
                    Start a new quote
                </a>

                @if ($business->quotation_disclaimer)
                    <p class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400 dark:text-gray-500 text-left whitespace-pre-line">
                        {{ $business->quotation_disclaimer }}
                    </p>
                @endif
            </div>
        </div>

        @include('public._powered-by')
        @include('public._embed-resize')
    </body>
</html>
