@props(['option' => null])

<div>
    <x-input-label for="label" value="Label" />
    <x-text-input id="label" name="label" type="text" class="block mt-1 w-full" required autofocus
        :value="old('label', $option?->label)" />
    <x-input-error :messages="$errors->get('label')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="price_modifier" value="Price Modifier ($)" />
    <x-text-input id="price_modifier" name="price_modifier" type="number" step="0.01" class="block mt-1 w-full" required
        :value="old('price_modifier', $option?->price_modifier ?? 0)" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Added to the base price when this option is picked. Use a negative number for a discount.</p>
    <x-input-error :messages="$errors->get('price_modifier')" class="mt-2" />
</div>
