@props(['country' => null])

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Country Name" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
            placeholder="e.g. Australia" :value="old('name', $country?->name)" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="short_code" value="Country Code (ISO alpha-2)" />
        <x-text-input id="short_code" name="short_code" type="text" maxlength="2" class="block mt-1 w-full uppercase" required
            placeholder="e.g. AU" :value="old('short_code', $country?->short_code)" />
        <x-input-error :messages="$errors->get('short_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="currency_code" value="Currency Code (ISO 4217)" />
        <x-text-input id="currency_code" name="currency_code" type="text" maxlength="3" class="block mt-1 w-full uppercase" required
            placeholder="e.g. AUD" :value="old('currency_code', $country?->currency_code)" />
        <x-input-error :messages="$errors->get('currency_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="currency_symbol" value="Currency Symbol" />
        <x-text-input id="currency_symbol" name="currency_symbol" type="text" maxlength="5" class="block mt-1 w-full"
            placeholder="e.g. A$" required :value="old('currency_symbol', $country?->currency_symbol)" />
        <x-input-error :messages="$errors->get('currency_symbol')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="currency_position" value="Symbol Position" />
        <select id="currency_position" name="currency_position" required
            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="before" @selected(old('currency_position', $country?->currency_position ?? 'before') === 'before')>Before amount ($29)</option>
            <option value="after" @selected(old('currency_position', $country?->currency_position) === 'after')>After amount (29 kr)</option>
        </select>
        <x-input-error :messages="$errors->get('currency_position')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <label class="flex items-center gap-2 mt-6">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('is_active', $country?->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-300">Active (shown on the registration form)</span>
        </label>
    </div>
</div>
