<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; font-size: 15px; line-height: 1.5; }

            .header-table { width: 100%; margin-bottom: 4px; }
            .header-table td { vertical-align: top; }
            .business-name { font-size: 25px; font-weight: bold; color: #111827; }
            .business-meta { color: #6b7280; font-size: 14px; margin-top: 2px; }
            .doc-title { font-size: 28px; font-weight: bold; color: #4f46e5; text-align: right; letter-spacing: 1px; }
            .doc-meta { text-align: right; font-size: 14px; color: #6b7280; margin-top: 6px; }
            .doc-meta div { margin-top: 2px; }
            .doc-meta strong { color: #111827; }

            .divider { border-top: 2px solid #4f46e5; margin: 12px 0 20px; }

            .parties-table { width: 100%; margin-bottom: 22px; }
            .parties-table td { vertical-align: top; width: 50%; }
            .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; color: #9ca3af; font-weight: bold; margin-bottom: 4px; }
            .party-name { font-size: 16px; font-weight: bold; color: #111827; }
            .party-line { font-size: 14.5px; color: #374151; margin-top: 1px; }

            .product-title { font-size: 19px; font-weight: bold; color: #111827; margin-bottom: 2px; }
            .product-desc { font-size: 14px; color: #6b7280; margin-bottom: 16px; }

            .selections-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
            .selections-table td { padding: 6px 0; font-size: 14.5px; border-bottom: 1px solid #f3f4f6; }
            .selections-table .sel-question { color: #6b7280; width: 55%; }
            .selections-table .sel-answer { color: #111827; font-weight: bold; text-align: right; }

            table.totals { width: 100%; border-collapse: collapse; }
            table.totals td { padding: 7px 0; font-size: 14.5px; }
            table.totals .t-label { color: #6b7280; }
            table.totals .t-amount { text-align: right; color: #111827; white-space: nowrap; }
            table.totals .t-row-line td { border-bottom: 1px solid #f3f4f6; }
            table.totals .grand-total td { border-top: 2px solid #111827; padding-top: 12px; font-size: 21px; font-weight: bold; color: #111827; }

            .disclaimer { margin-top: 24px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 12px; line-height: 1.5; color: #9ca3af; white-space: pre-line; }

            .footer { margin-top: 24px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 12.5px; color: #9ca3af; text-align: center; }
        </style>
    </head>
    <body>
        @php
            $demoBusiness = $quote->demoBusinessOverride();
            $displayName = $demoBusiness['name'] ?? $business->name;
            $selections = $quote->meta['selections'] ?? [];
            $customerContact = $quote->customerContact();

            // A demo quote (Super Admin testing a template) shows only
            // whatever was typed into that one quote's Demo Business
            // Details — never the template's own real data. Every ordinary
            // quote falls back to the business's actual saved address/
            // phone/reply-to email.
            $businessAddressLines = $demoBusiness
                ? array_filter([$demoBusiness['address'] ?? null])
                : $business->addressLines();
            $businessPhone = $demoBusiness['phone'] ?? $business->phone;
            $businessEmail = $demoBusiness['email'] ?? $business->notification_email;
        @endphp

        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <div class="business-name">{{ $displayName }}</div>
                    @foreach ($businessAddressLines as $line)
                        <div class="business-meta">{{ $line }}</div>
                    @endforeach
                    @if ($businessPhone || $businessEmail)
                        <div class="business-meta">{{ implode(' · ', array_filter([$businessPhone, $businessEmail])) }}</div>
                    @endif
                </td>
                <td style="width: 45%;">
                    <div class="doc-title">QUOTATION</div>
                    <div class="doc-meta">
                        <div>Quote #<strong>{{ $quote->id }}</strong></div>
                        <div>Date: <strong>{{ $quote->created_at->format('M j, Y') }}</strong></div>
                        @if ($quote->expires_at)
                            <div>{{ $quote->isExpired() ? 'Expired:' : 'Valid until:' }} <strong>{{ $quote->expires_at->format('M j, Y') }}</strong></div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table class="parties-table">
            <tr>
                <td>
                    <div class="label">Prepared For</div>
                    <div class="party-name">{{ $quote->customer_name }}</div>
                    <div class="party-line">{{ $quote->customer_email }}</div>
                    @if (! empty($customerContact['phone']))
                        <div class="party-line">{{ $customerContact['phone'] }}</div>
                    @endif
                    @foreach ($customerContact['address_lines'] ?? [] as $line)
                        <div class="party-line">{{ $line }}</div>
                    @endforeach
                </td>
                <td>
                    <div class="label">Quote For</div>
                    <div class="party-name">{{ $product->name }}</div>
                    @if ($product->description)
                        <div class="party-line">{{ $product->description }}</div>
                    @endif
                </td>
            </tr>
        </table>

        @if (count($selections))
            <div class="label">Selections</div>
            <table class="selections-table">
                @foreach ($selections as $item)
                    <tr>
                        <td class="sel-question">{{ $item['question'] }}</td>
                        <td class="sel-answer">{{ $item['answer'] }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <table class="totals">
            <tr class="t-row-line">
                <td class="t-label">Base price</td>
                <td class="t-amount">${{ number_format($basePrice, 2) }}</td>
            </tr>

            @foreach ($appliedRules as $applied)
                <tr class="t-row-line">
                    <td class="t-label">{{ $applied['name'] }}</td>
                    <td class="t-amount">{{ $applied['amount_changed'] >= 0 ? '+' : '-' }}${{ number_format(abs($applied['amount_changed']), 2) }}</td>
                </tr>
            @endforeach

            @if (! empty($quote->meta['discount']))
                <tr class="t-row-line">
                    <td class="t-label">Discount ({{ $quote->meta['discount']['type'] === 'percentage' ? rtrim(rtrim(number_format($quote->meta['discount']['value'], 2), '0'), '.').'% off' : '$'.number_format($quote->meta['discount']['value'], 2).' off' }})</td>
                    <td class="t-amount">&minus;${{ number_format($quote->meta['discount']['amount'], 2) }}</td>
                </tr>
            @endif

            @if (count($tax['tax_lines']))
                <tr class="t-row-line">
                    <td class="t-label">Subtotal</td>
                    <td class="t-amount">${{ number_format($tax['subtotal'], 2) }}</td>
                </tr>
                @foreach ($tax['tax_lines'] as $taxLine)
                    <tr class="t-row-line">
                        <td class="t-label">{{ $taxLine['title'] }} ({{ rtrim(rtrim(number_format($taxLine['rate'], 3), '0'), '.') }}%)</td>
                        <td class="t-amount">${{ number_format($taxLine['amount'], 2) }}</td>
                    </tr>
                @endforeach
            @endif

            <tr class="grand-total">
                <td>Total</td>
                <td class="t-amount">${{ number_format($quote->final_price, 2) }}</td>
            </tr>
        </table>

        @if ($business->quotation_disclaimer)
            <div class="disclaimer">{{ $business->quotation_disclaimer }}</div>
        @endif

        <div class="footer">
            This quotation was prepared for {{ $quote->customer_name }} on {{ $quote->created_at->format('M j, Y') }} &middot; Quote #{{ $quote->id }}
            @if ($quote->prepared_by_name)
                <br>Prepared by {{ $quote->prepared_by_name }} ({{ $quote->prepared_by_email }})
            @endif
        </div>
    </body>
</html>
