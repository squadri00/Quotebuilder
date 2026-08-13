<x-mail::message>
# New support ticket — {{ $ticket->tracking_number }}

**{{ $business->name }}** ({{ $submitter->email }}) submitted a new support ticket.

**Subject:** {{ $ticket->subject }}

**Message:**<br>
{{ $ticket->message }}

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
