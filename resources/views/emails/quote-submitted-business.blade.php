<x-mail::message>
# New quote submitted

**{{ $quote->customer_name }}** ({{ $quote->customer_email }}) just requested a quote for **{{ $product->name }}**.

**Price:** ${{ number_format($quote->final_price, 2) }}
**Submitted:** {{ $quote->created_at->format('M j, Y g:ia') }}

@if ($inboxUrl)
<x-mail::button :url="$inboxUrl">
View in Quote Inbox
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
