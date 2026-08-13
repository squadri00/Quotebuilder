@props(['product' => null])

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
        :value="old('name', $product?->name)" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">{{ old('description', $product?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="base_price" value="Base Price ($)" />
    <x-text-input id="base_price" name="base_price" type="number" step="0.01" min="0" class="block mt-1 w-full" required
        :value="old('base_price', $product?->base_price)" />
    <x-input-error :messages="$errors->get('base_price')" class="mt-2" />
</div>

<div class="mt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 brand-checkbox shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $product?->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-300">Active (visible to customers)</span>
    </label>
    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
</div>
