<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Public Quote Settings') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Applies only to quotes customers build themselves on your public quote page — never to quotes your staff create internally.') }}
        </p>
    </header>

    <form method="post" action="{{ route('business.public-quote-settings.update') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="public_quote_validity_days" :value="__('Default Validity')" />
            <select id="public_quote_validity_days" name="public_quote_validity_days"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="" @selected(old('public_quote_validity_days', $business->public_quote_validity_days) === null)>No expiration</option>
                @foreach (\App\Models\Quote::EXPIRATION_DAY_OPTIONS as $days)
                    <option value="{{ $days }}" @selected((int) old('public_quote_validity_days', $business->public_quote_validity_days) === $days)>{{ $days }} days</option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __("Every quote a customer builds themselves gets a \"valid until\" date this many days out. Leave as \"No expiration\" to skip this — the same as today.") }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('public_quote_validity_days')" />
        </div>

        <div class="flex items-start gap-3">
            <input type="checkbox" id="public_pdf_download_enabled" name="public_pdf_download_enabled" value="1"
                class="mt-1 rounded border-gray-300 brand-checkbox shadow-sm focus:ring-indigo-500"
                @checked(old('public_pdf_download_enabled', $business->public_pdf_download_enabled))>
            <div>
                <x-input-label for="public_pdf_download_enabled" :value="__('Allow customers to download a PDF')" />
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Still requires your plan to include PDF downloads — this just lets you switch it off even when it does.') }}</p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <input type="checkbox" id="public_email_enabled" name="public_email_enabled" value="1"
                class="mt-1 rounded border-gray-300 brand-checkbox shadow-sm focus:ring-indigo-500"
                @checked(old('public_email_enabled', $business->public_email_enabled))>
            <div>
                <x-input-label for="public_email_enabled" :value="__('Email quote confirmations')" />
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __("Sends the customer's confirmation email and your team's new-quote notification. Still requires your plan to include email notifications — this just lets you switch it off even when it does.") }}</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'business-public-quote-settings-updated')
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
