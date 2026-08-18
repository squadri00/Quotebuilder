<section x-data="{
        format: {{ Js::from(old('quote_number_format', $business->quote_number_format)) }},
        prefix: {{ Js::from(old('quote_number_prefix', $business->quote_number_prefix)) }},
        next: {{ Js::from((string) old('quote_number_next', $business->quote_number_next)) }},
        get preview() {
            const n = parseInt(this.next, 10);
            const safeNext = Number.isFinite(n) && n > 0 ? n : 1;
            if (this.format === 'alphanumeric') {
                return (this.prefix || '').trim() + String(safeNext).padStart(4, '0');
            }
            return String(safeNext);
        },
    }">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Quote Numbering') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('The number shown to customers and printed on quotes/invoices — separate from the internal ID used everywhere else in the system.') }}
        </p>
    </header>

    <form method="post" action="{{ route('business.quote-numbering.update') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="quote_number_format" :value="__('Number Style')" />
            <select id="quote_number_format" name="quote_number_format" x-model="format"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="numeric" @selected(old('quote_number_format', $business->quote_number_format) === 'numeric')>Numeric — e.g. 1042</option>
                <option value="alphanumeric" @selected(old('quote_number_format', $business->quote_number_format) === 'alphanumeric')>Letters + Numbers — e.g. INV-1042</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('quote_number_format')" />
        </div>

        <div x-show="format === 'alphanumeric'" x-cloak>
            <x-input-label for="quote_number_prefix" :value="__('Prefix')" />
            <x-text-input id="quote_number_prefix" name="quote_number_prefix" type="text" class="mt-1 block w-full"
                x-model="prefix" maxlength="20" placeholder="INV-" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Put in front of every number, e.g. "INV-" gives INV-1042. Just the letters/symbol — the starting number goes in Next Number below, not here.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('quote_number_prefix')" />
        </div>

        <div>
            <x-input-label for="quote_number_next" :value="__('Next Number')" />
            <x-text-input id="quote_number_next" name="quote_number_next" type="number" min="1" class="mt-1 block w-full"
                x-model="next" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('The number the next new quote will get. Already-issued quotes keep the numbers they were given — changing this only affects quotes created from now on.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('quote_number_next')" />
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-4 py-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Next quote will be numbered</p>
            <p class="mt-1 text-lg font-mono font-semibold text-gray-900 dark:text-gray-100" x-text="preview"></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="format === 'alphanumeric' && /\d/.test(prefix)" x-cloak>
                Your prefix has a number in it — if that number was meant to be the starting count, move it into Next Number above instead and keep this field as just the letters/symbol (e.g. "INV-").
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'business-quote-numbering-updated')
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
