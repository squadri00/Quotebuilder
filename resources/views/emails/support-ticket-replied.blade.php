<x-mail::message>
# A reply to your support request

Someone from the {{ config('app.name') }} team replied to your ticket **{{ $ticket->tracking_number }}** ({{ $ticket->subject }}):

{{ $reply->message }}

<x-mail::button :url="$ticketUrl">
View &amp; Reply
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
