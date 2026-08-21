@props(['option' => null])

<div>
    <x-input-label for="label" value="Label" />
    <x-text-input id="label" name="label" type="text" class="block mt-1 w-full" required autofocus
        :value="old('label', $option?->label)" />
    <x-input-error :messages="$errors->get('label')" class="mt-2" />
</div>

<div class="mt-4" x-data="{ count: {{ Js::from(strlen(old('description', $option?->description ?? ''))) }} }">
    <x-input-label for="description" value="Help Text (optional)" />
    <textarea id="description" name="description" rows="2" maxlength="160" @input="count = $event.target.value.length"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('description', $option?->description) }}</textarea>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shown as an "i" tooltip next to this option in the quote builder. <span x-text="count"></span>/160</p>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="price_modifier" value="Price Modifier ($)" />
    <x-text-input id="price_modifier" name="price_modifier" type="number" step="0.01" class="block mt-1 w-full" required
        :value="old('price_modifier', $option?->price_modifier ?? 0)" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Added to the base price when this option is picked. Use a negative number for a discount.</p>
    <x-input-error :messages="$errors->get('price_modifier')" class="mt-2" />
</div>
