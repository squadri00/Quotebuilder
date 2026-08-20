<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Business Details') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Your business name and full address — shown on invoices and used for account records.') }}
        </p>
    </header>

    <form method="post" action="{{ route('business.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="business_name" :value="__('Business Name')" />
            <x-text-input id="business_name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $business->name)" required autocomplete="organization" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="address_line1" :value="__('Address Line 1')" />
            <x-text-input id="address_line1" name="address_line1" type="text" class="mt-1 block w-full" :value="old('address_line1', $business->address_line1)" autocomplete="address-line1" />
            <x-input-error class="mt-2" :messages="$errors->get('address_line1')" />
        </div>

        <div>
            <x-input-label for="address_line2" :value="__('Address Line 2')" />
            <x-text-input id="address_line2" name="address_line2" type="text" class="mt-1 block w-full" :value="old('address_line2', $business->address_line2)" autocomplete="address-line2" />
            <x-input-error class="mt-2" :messages="$errors->get('address_line2')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <x-input-label for="city" :value="__('City')" />
                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $business->city)" autocomplete="address-level2" />
                <x-input-error class="mt-2" :messages="$errors->get('city')" />
            </div>

            <div>
                <x-input-label for="state_province" :value="__('State / Province')" />
                <x-text-input id="state_province" name="state_province" type="text" class="mt-1 block w-full" :value="old('state_province', $business->state_province)" autocomplete="address-level1" />
                <x-input-error class="mt-2" :messages="$errors->get('state_province')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <x-input-label for="postal_code" :value="__('Postal Code')" />
                <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $business->postal_code)" autocomplete="postal-code" />
                <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
            </div>

            <div>
                <x-input-label for="country" :value="__('Country')" />
                <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $business->country)" autocomplete="country-name" />
                <x-input-error class="mt-2" :messages="$errors->get('country')" />
            </div>
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Time Zone')" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Used to show the correct local date and time on your quotes — in the inbox, on the customer-facing result page, and on PDFs.') }}
            </p>
            <select id="timezone" name="timezone"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="" @selected(! old('timezone', $business->timezone))>{{ __('Use platform default (:tz)', ['tz' => config('app.timezone')]) }}</option>
                @foreach (\App\Support\TimezoneOptions::grouped() as $region => $zones)
                    <optgroup label="{{ $region }}">
                        @foreach ($zones as $identifier => $label)
                            <option value="{{ $identifier }}" @selected(old('timezone', $business->timezone) === $identifier)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $business->phone)" autocomplete="tel" placeholder="(555) 123-4567" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Shown on your quote PDFs, next to your address.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="notification_email" :value="__('Quote Reply-To Email')" />
            <x-text-input id="notification_email" name="notification_email" type="email" class="mt-1 block w-full" :value="old('notification_email', $business->notification_email)" autocomplete="email" placeholder="office@yourbusiness.com" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __("When a customer's quote email arrives, hitting Reply sends it here instead of back to us. Leave blank to skip this.") }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('notification_email')" />
        </div>

        <div>
            <x-input-label for="quotation_disclaimer" :value="__('Quotation Disclaimer')" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Shown to the customer on the quote screen and the PDF — terms, validity period, exclusions, anything you want them to see with every quote.') }}
            </p>
            <textarea id="quotation_disclaimer" name="quotation_disclaimer" rows="4"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
            >{{ old('quotation_disclaimer', $business->quotation_disclaimer) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('quotation_disclaimer')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'business-details-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
