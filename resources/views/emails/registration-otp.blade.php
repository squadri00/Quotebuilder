<x-mail::message>
# Verify your email

Hi {{ $name }},

Enter this code to finish setting up your free {{ config('app.name') }} account:

<x-mail::panel>
<div style="font-size: 32px; letter-spacing: 8px; text-align: center; font-weight: bold;">
{{ $code }}
</div>
</x-mail::panel>

This code expires in 10 minutes. If you didn't request this, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
