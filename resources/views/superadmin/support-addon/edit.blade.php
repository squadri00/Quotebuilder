<x-superadmin-layout title="Support Add-on">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Support Add-on</h2>
    </x-slot>

    <p class="max-w-xl mb-4 text-sm text-gray-500 dark:text-gray-400">
        Priority Support is a standalone subscription, independent of a business's plan — a business can buy it (or cancel it) regardless of which plan they're on. Turning this off only blocks new signups; it does not cancel any business that's already subscribed — that still has to be cancelled on their account or in Stripe.
    </p>

    <x-auth-session-status class="max-w-xl mb-4" :status="session('status')" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.support-addon.update') }}">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required
                    :value="old('name', $supportAddon->name)" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="price" value="Price ($/month)" />
                <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="block mt-1 w-full" required
                    :value="old('price', $supportAddon->price)" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Display price only — the actual amount charged comes from the Stripe Price below. Keep these in sync.</p>
                <x-input-error :messages="$errors->get('price')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="stripe_price_id" value="Stripe Price ID" />
                <x-text-input id="stripe_price_id" name="stripe_price_id" type="text" class="block mt-1 w-full font-mono text-sm"
                    placeholder="price_..." :value="old('stripe_price_id', $supportAddon->stripe_price_id)" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">A recurring monthly Stripe Price, separate from any plan's price. Required before this can be turned on.</p>
                <x-input-error :messages="$errors->get('stripe_price_id')" class="mt-2" />
            </div>

            <div class="mt-4">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
                        @checked(old('is_active', $supportAddon->is_active))>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active (businesses can subscribe)</span>
                </label>
                @if (! $supportAddon->stripe_price_id)
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">Set a Stripe Price ID above before turning this on — otherwise businesses would have nothing to check out with.</p>
                @endif
            </div>

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
