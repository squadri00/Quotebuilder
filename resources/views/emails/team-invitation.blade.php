<x-mail::message>
# You've been invited to join {{ $business->name }}

{{ $invitation->invitedBy?->name ?? 'Someone' }} has invited you to join **{{ $business->name }}** on {{ config('app.name') }} as {{ $invitation->role === 'admin' ? 'an Admin' : 'a Member' }}.

Click below to set your password and get started. This link expires on {{ $invitation->expires_at->format('M j, Y') }}.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

If you weren't expecting this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
