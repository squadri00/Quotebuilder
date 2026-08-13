<form method="POST" action="{{ route('register') }}">
    @csrf

    @if ($selectedPlan)
        <input type="hidden" name="plan_id" value="{{ $selectedPlan->id }}">
        <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">
            <p class="text-sm font-medium text-indigo-900">
                Selected plan: {{ $selectedPlan->name }} — ${{ number_format($selectedPlan->price, 0) }}/{{ $selectedPlan->billing_interval === 'yearly' ? 'yr' : 'mo' }}
            </p>
            <a href="{{ route('pricing') }}" class="text-xs font-medium text-indigo-700 hover:text-indigo-900 underline">Change plan</a>
        </div>
    @endif

    <!-- Name -->
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <!-- Company Name -->
    <div class="mt-4">
        <x-input-label for="company_name" :value="__('Company Name')" />
        <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autocomplete="organization" />
        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
    </div>

    {{--
        Only two countries are supported today, so both are a fixed
        dropdown rather than free text — and the State/Province list
        switches to match whichever one is selected (Canadian provinces
        vs US states). PlatformTaxCalculator only ever taxes Canadian
        addresses (see its HOME_COUNTRY_CODES check against "Canada"/"CA"),
        so the exact US state value is stored but never keyed on for tax.
    --}}
    <div
        class="mt-4"
        x-data="{
            country: @js(old('country') === 'United States' ? 'United States' : 'Canada'),
            stateProvince: @js(old('state_province', '')),
            canadaProvinces: @js(\App\Support\ProvinceCodes::options()),
            usStates: @js(\App\Support\StateCodes::options()),
            get options() {
                return this.country === 'United States' ? this.usStates : this.canadaProvinces;
            },
        }"
        x-effect="if (! (stateProvince in options)) stateProvince = ''"
    >
        <x-input-label for="country" :value="__('Country')" />
        {{--
            Required only when a paid plan is selected — this is the only
            place country/province get captured now that the checkout page
            no longer asks again (see partials/checkout-content.blade.php),
            so a blank value here would silently undercharge tax on a real
            Stripe subscription instead of just getting caught downstream.
        --}}
        <select id="country" name="country" x-model="country" @if ($selectedPlan) required @endif
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
            <option value="Canada">Canada</option>
            <option value="United States">United States</option>
        </select>
        <x-input-error :messages="$errors->get('country')" class="mt-2" />

        <div class="mt-4">
            <x-input-label for="state_province" :value="__('State / Province')" />
            <select id="state_province" name="state_province" x-model="stateProvince" @if ($selectedPlan) required @endif
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
                <option value="">— Select —</option>
                <template x-for="[code, name] in Object.entries(options)" :key="code">
                    <option :value="code" x-text="name + ' (' + code + ')'"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-gray-500">Used to work out any tax that applies to your subscription{{ $selectedPlan ? '.' : " — leave blank if you're not subscribing to a paid plan right now." }}</p>
            <x-input-error :messages="$errors->get('state_province')" class="mt-2" />
        </div>
    </div>

    <!-- Email Address -->
    <div class="mt-4">
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <!-- Password -->
    <div class="mt-4">
        <x-input-label for="password" :value="__('Password')" />

        <x-text-input id="password" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="new-password" />

        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <!-- Confirm Password -->
    <div class="mt-4">
        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

        <x-text-input id="password_confirmation" class="block mt-1 w-full"
                        type="password"
                        name="password_confirmation" required autocomplete="new-password" />

        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end mt-4">
        <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
            {{ __('Already registered?') }}
        </a>

        <x-primary-button class="ms-4">
            {{ __('Register') }}
        </x-primary-button>
    </div>
</form>
