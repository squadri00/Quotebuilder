@props(['taxRate' => null])

<div>
    <x-input-label for="title" value="Title" />
    <x-text-input id="title" name="title" type="text" class="block mt-1 w-full" required autofocus
        placeholder="e.g. GST, HST, VAT, Sales Tax" :value="old('title', $taxRate?->title)" />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Description (optional)" />
    <x-text-input id="description" name="description" type="text" class="block mt-1 w-full"
        placeholder="e.g. Federal Goods and Services Tax" :value="old('description', $taxRate?->description)" />
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="rate" value="Rate (%)" />
    <x-text-input id="rate" name="rate" type="number" step="0.001" min="0" max="100" class="block mt-1 w-full" required
        :value="old('rate', $taxRate?->rate)" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Applied to the quote's subtotal. Add one line per tax you charge — e.g. Ontario: one HST line; Quebec: separate GST + QST lines. Leave no lines at all if you don't charge sales tax.</p>
    <x-input-error :messages="$errors->get('rate')" class="mt-2" />
</div>

<div class="mt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 brand-checkbox shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $taxRate?->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-300">Active (included in quote calculations)</span>
    </label>
    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
</div>
