<x-superadmin-layout title="Change Password">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Change Password</h2>
    </x-slot>

    <p class="max-w-xl mb-4 text-sm text-gray-500 dark:text-gray-400">
        Update the password for your own Super Admin login ({{ Auth::guard('admin')->user()->email }}). This only changes your account — it has no effect on any business's login.
    </p>

    <x-auth-session-status class="max-w-xl mb-4" :status="session('status')" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.password.update') }}">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="current_password" value="Current Password" />
                <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" required />
                <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" value="New Password" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" value="Confirm New Password" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center gap-4 mt-6">
                <x-primary-button>Update Password</x-primary-button>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
