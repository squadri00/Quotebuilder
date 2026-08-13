<x-guest-layout>
    <p class="text-sm text-gray-600">
        This invitation to join <strong>{{ $invitation->business->name }}</strong> has expired. Ask the account owner to send you a new one from their Team page.
    </p>

    <div class="mt-4">
        <a href="{{ route('login') }}" class="text-sm brand-text underline">Back to login</a>
    </div>
</x-guest-layout>
