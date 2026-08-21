<x-mail::message>
# New contact form message

**From:** {{ $name }} ({{ $email }})<br>
**Subject:** {{ $topic }}

**Message:**<br>
{{ $body }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
