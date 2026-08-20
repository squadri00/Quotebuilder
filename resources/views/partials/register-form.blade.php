<form method="POST" action="{{ route('register') }}">
    @csrf

    @if ($selectedPlan)
        <input type="hidden" name="plan_id" value="{{ $selectedPlan->id }}">
        <div class="mb-4 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/30 px-4 py-3">
            <p class="text-sm font-medium text-indigo-900 dark:text-indigo-200">
                Selected plan: {{ $selectedPlan->name }} — {{ \App\Models\PlatformSetting::formatPrice($selectedPlan->price) }}/{{ $selectedPlan->billing_interval === 'yearly' ? 'yr' : 'mo' }}
            </p>
            <a href="{{ route('pricing') }}" class="text-xs font-medium text-indigo-700 dark:text-indigo-300 hover:text-indigo-900 dark:hover:text-indigo-100 underline">Change plan</a>
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
        Country options come from the Countries table (Super Admin >
        Countries), so adding a country there is enough to open
        registration to it here — no code change needed. Canada and the
        US get a real State/Province dropdown (needed for
        PlatformTaxCalculator's per-province lookup and for US state
        data); any other country gets a plain free-text region field,
        since we don't hold subdivision lists for the rest of the world.
        PlatformTaxCalculator only ever taxes Canadian addresses (see its
        HOME_COUNTRY_CODES check against "Canada"/"CA"), so a non-Canadian
        value here is stored but never keyed on for tax.
    --}}
    <div
        class="mt-4"
        x-data="{
            country: @js(old('country', $countries->firstWhere('name', 'Canada')?->name ?? $countries->first()?->name)),
            stateProvince: @js(old('state_province', '')),
            canadaProvinces: @js(\App\Support\ProvinceCodes::options()),
            usStates: @js(\App\Support\StateCodes::options()),
            get options() {
                if (this.country === 'Canada') return this.canadaProvinces;
                if (this.country === 'United States') return this.usStates;
                return null;
            },
        }"
        x-effect="if (options && ! (stateProvince in options)) stateProvince = ''"
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
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
            @foreach ($countries as $option)
                <option value="{{ $option->name }}">{{ $option->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('country')" class="mt-2" />

        <div class="mt-4">
            <x-input-label for="state_province" :value="__('State / Province / Region')" />
            <template x-if="options">
                <select id="state_province" name="state_province" x-model="stateProvince" @if ($selectedPlan) required @endif
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">— Select —</option>
                    <template x-for="[code, name] in Object.entries(options)" :key="code">
                        <option :value="code" x-text="name + ' (' + code + ')'"></option>
                    </template>
                </select>
            </template>
            <template x-if="! options">
                <input id="state_province" name="state_province" type="text" x-model="stateProvince" @if ($selectedPlan) required @endif
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-400"
                    placeholder="e.g. your state, region, or county">
            </template>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Used to work out any tax that applies to your subscription{{ $selectedPlan ? '.' : " — leave blank if you're not subscribing to a paid plan right now." }}</p>
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
        <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
            {{ __('Already registered?') }}
        </a>

        <x-primary-button class="ms-4">
            {{ __('Register') }}
        </x-primary-button>
    </div>
</form>
