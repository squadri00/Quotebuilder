@php
    $demoBusiness = $quote->demoBusinessOverride();
    $displayName = $demoBusiness['name'] ?? $business->name;
@endphp
<x-mail::message>
# Your quote from {{ $displayName }}

Hi {{ $quote->customer_name }}, thanks for requesting a quote for **{{ $product->name }}**.

## ${{ number_format($quote->final_price, 2) }}

@if (count($appliedRules))
@foreach ($appliedRules as $applied)
{{ $applied['name'] }}: {{ $applied['amount_changed'] >= 0 ? '+' : '-' }}${{ number_format(abs($applied['amount_changed']), 2) }}
@endforeach
@endif

@if (count($taxLines))
Subtotal: ${{ number_format($subtotalBeforeTax, 2) }}
@foreach ($taxLines as $taxLine)
{{ $taxLine['title'] }} ({{ rtrim(rtrim(number_format($taxLine['rate'], 3), '0'), '.') }}%): ${{ number_format($taxLine['amount'], 2) }}
@endforeach
@endif

@if ($viewUrl)
<x-mail::button :url="$viewUrl">
View Your Quote
</x-mail::button>

You can revisit this quote any time using the button above.
@endif

Thanks,<br>
{{ $displayName }}
@if (! empty($demoBusiness['address']) || ! empty($demoBusiness['phone']) || ! empty($demoBusiness['email']))
<br>
@if (! empty($demoBusiness['address'])){{ $demoBusiness['address'] }}<br>@endif
@if (! empty($demoBusiness['phone'])){{ $demoBusiness['phone'] }}<br>@endif
@if (! empty($demoBusiness['email'])){{ $demoBusiness['email'] }}@endif
@endif
</x-mail::message>
