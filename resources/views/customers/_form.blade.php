@php $customer ??= null; @endphp

<div>
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $customer?->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div class="mt-6">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $customer?->email)" required />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div class="mt-6">
    <x-input-label for="phone" :value="__('Phone')" />
    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $customer?->phone)" />
    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
</div>

<div class="mt-6">
    <x-input-label for="address_line1" :value="__('Address Line 1')" />
    <x-text-input id="address_line1" name="address_line1" type="text" class="mt-1 block w-full" :value="old('address_line1', $customer?->address_line1)" />
    <x-input-error class="mt-2" :messages="$errors->get('address_line1')" />
</div>

<div class="mt-6">
    <x-input-label for="address_line2" :value="__('Address Line 2')" />
    <x-text-input id="address_line2" name="address_line2" type="text" class="mt-1 block w-full" :value="old('address_line2', $customer?->address_line2)" />
    <x-input-error class="mt-2" :messages="$errors->get('address_line2')" />
</div>

<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="city" :value="__('City')" />
        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $customer?->city)" />
        <x-input-error class="mt-2" :messages="$errors->get('city')" />
    </div>

    <div>
        <x-input-label for="state_province" :value="__('State / Province')" />
        <x-text-input id="state_province" name="state_province" type="text" class="mt-1 block w-full" :value="old('state_province', $customer?->state_province)" />
        <x-input-error class="mt-2" :messages="$errors->get('state_province')" />
    </div>
</div>

<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="postal_code" :value="__('Postal Code')" />
        <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $customer?->postal_code)" />
        <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
    </div>

    <div>
        <x-input-label for="country" :value="__('Country')" />
        <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $customer?->country)" />
        <x-input-error class="mt-2" :messages="$errors->get('country')" />
    </div>
</div>

<div class="mt-6">
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" rows="3"
        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
    >{{ old('notes', $customer?->notes) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
</div>
