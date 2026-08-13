@props(['tier' => null])

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
        placeholder="e.g. 30 Products" :value="old('name', $tier?->name)" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="product_count" value="Product Count" />
        <x-text-input id="product_count" name="product_count" type="number" min="1" class="block mt-1 w-full" required
            :value="old('product_count', $tier?->product_count)" />
        <x-input-error :messages="$errors->get('product_count')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="price" value="Price ($, one-time)" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="block mt-1 w-full" required
            :value="old('price', $tier?->price)" />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="stripe_price_id" value="Stripe Price ID" />
    <x-text-input id="stripe_price_id" name="stripe_price_id" type="text" class="block mt-1 w-full font-mono text-sm"
        placeholder="price_..." :value="old('stripe_price_id', $tier?->stripe_price_id)" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">A one-time (not recurring) Stripe Price for this package. Leave blank until it's ready — the package just won't be purchasable yet.</p>
    <x-input-error :messages="$errors->get('stripe_price_id')" class="mt-2" />
</div>

<div class="mt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $tier?->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-300">Active (shown to businesses on the Billing page)</span>
    </label>
</div>
