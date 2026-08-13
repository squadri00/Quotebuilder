<x-guest-layout>
    <p class="mb-4 text-sm text-gray-600">
        You've been invited to join <strong>{{ $invitation->business->name }}</strong> as {{ $invitation->role === 'admin' ? 'an Admin' : 'a Member' }}. Set a password to get started.
    </p>

    <form method="POST" action="{{ route('team.accept.store', $invitation->token) }}">
        @csrf

        <div>
            <x-input-label value="Name" />
            <p class="mt-1 text-gray-900">{{ $invitation->name }}</p>
        </div>

        <div class="mt-4">
            <x-input-label value="Email" />
            <p class="mt-1 text-gray-900">{{ $invitation->email }}</p>
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <x-primary-button>
                {{ __('Set Password & Join') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
