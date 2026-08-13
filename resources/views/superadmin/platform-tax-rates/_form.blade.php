@props(['rate' => null])

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="country_code" value="Country Code" />
        <x-text-input id="country_code" name="country_code" type="text" class="block mt-1 w-full uppercase" maxlength="2" required
            placeholder="CA" :value="old('country_code', $rate?->country_code ?? 'CA')" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">2-letter ISO code. Only businesses in this country are ever charged platform tax.</p>
        <x-input-error :messages="$errors->get('country_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="province" value="Province (optional)" />
        <x-text-input id="province" name="province" type="text" class="block mt-1 w-full"
            placeholder="ON, or leave blank for default" :value="old('province', $rate?->province)" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to make this the default rate for any province not listed on its own row.</p>
        <x-input-error :messages="$errors->get('province')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="tax_label" value="Tax Label" />
        <x-text-input id="tax_label" name="tax_label" type="text" class="block mt-1 w-full" required
            placeholder="e.g. HST, GST" :value="old('tax_label', $rate?->tax_label)" />
        <x-input-error :messages="$errors->get('tax_label')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="rate" value="Rate (%)" />
        <x-text-input id="rate" name="rate" type="number" step="0.001" min="0" max="100" class="block mt-1 w-full" required
            :value="old('rate', $rate?->rate)" />
        <x-input-error :messages="$errors->get('rate')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $rate?->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
    </label>
</div>
