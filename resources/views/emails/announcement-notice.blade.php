<x-mail::message>
# {{ $announcement->title }}

Hi {{ $recipientName }},

{{ $announcement->message }}

<p style="color:#9ca3af;font-size:12px;">This is a platform announcement from {{ config('app.name') }}.</p>
</x-mail::message>
