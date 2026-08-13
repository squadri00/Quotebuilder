<x-mail::message>
# We've received your support request

Thanks for reaching out. Your tracking number is:

**{{ $ticket->tracking_number }}**

**Subject:** {{ $ticket->subject }}

We'll get back to you soon. You can follow up any time from your {{ config('app.name') }} dashboard.

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
