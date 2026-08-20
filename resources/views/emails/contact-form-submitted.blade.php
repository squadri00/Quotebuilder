<x-mail::message>
# New contact form message

**From:** {{ $name }} ({{ $email }})

**Message:**<br>
{{ $body }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
