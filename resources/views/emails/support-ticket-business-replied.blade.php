<x-mail::message>
# New reply — {{ $ticket->tracking_number }}

**{{ $ticket->business->name }}** replied to their ticket ({{ $ticket->subject }}):

{{ $reply->message }}

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
