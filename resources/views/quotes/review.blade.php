<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Review Quote</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div
        class="max-w-3xl mx-auto"
        x-data="{
            override: '',
            discountType: '',
            discountValue: '',
            existingCustomers: {{ Js::from($existingCustomers) }},
            customerMode: 'new',
            customerSearch: '',
            selectedCustomer: null,
            get discountAmount() {
                const value = parseFloat(this.discountValue);
                if (! this.discountType || isNaN(value) || value <= 0) return 0;
                const base = Number({{ $calculatedPrice }});
                const amount = this.discountType === 'percentage' ? base * (Math.min(value, 100) / 100) : value;
                return Math.min(Math.round(amount * 100) / 100, base);
            },
            get discountedPrice() {
                return Math.max(0, Number({{ $calculatedPrice }}) - this.discountAmount);
            },
            get displayPrice() {
                return this.override !== '' && ! isNaN(this.override) ? parseFloat(this.override) : this.discountedPrice;
            },
            get filteredCustomers() {
                if (this.customerSearch.length < 1) return [];
                const term = this.customerSearch.toLowerCase();
                return this.existingCustomers
                    .filter(c => c.name.toLowerCase().includes(term) || c.email.toLowerCase().includes(term))
                    .slice(0, 8);
            },
            pickCustomer(customer) {
                this.selectedCustomer = customer;
                this.customerSearch = customer.name + ' — ' + customer.email;
            },
            clearCustomer() {
                this.selectedCustomer = null;
                this.customerSearch = '';
            },
        }"
    >
    <div class="flex flex-col md:flex-row gap-6">
        @if (count($selections))
            <div class="md:w-56 shrink-0 md:order-1 order-2">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Selections</p>
                    <ul class="space-y-2">
                        @foreach ($selections as $item)
                            <li>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['question'] }}</span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['answer'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="flex-1 order-1 md:order-2 max-w-lg">
        <x-card>
            <div class="text-center">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    Not saved yet
                </span>

                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ $product->name }}</p>

                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">Price</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-gray-100 mt-1" x-text="'$' + displayPrice.toFixed(2)">
                    ${{ number_format($calculatedPrice, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" x-show="discountAmount > 0" x-cloak>
                    Discount: &minus;$<span x-text="discountAmount.toFixed(2)"></span> &middot; before discount: ${{ number_format($calculatedPrice, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" x-show="override !== '' && ! isNaN(override) && parseFloat(override).toFixed(2) !== discountedPrice.toFixed(2)" x-cloak>
                    System-calculated price is $<span x-text="discountedPrice.toFixed(2)"></span>
                </p>

                @if (count($result['applied_rules']))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Adjustments</p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            @foreach ($result['applied_rules'] as $applied)
                                <li class="flex justify-between gap-4">
                                    <span>{{ $applied['name'] }}</span>
                                    <span class="shrink-0">
                                        {{ $applied['amount_changed'] >= 0 ? '+' : '-' }}${{ number_format(abs($applied['amount_changed']), 2) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (count($tax['tax_lines']))
                    <div class="mt-6 text-left border-t border-gray-200 dark:border-gray-700 pt-4">
                        <ul class="space-y-1 text-sm">
                            <li class="flex justify-between gap-4 text-gray-600 dark:text-gray-300">
                                <span>Subtotal</span>
                                <span class="shrink-0">${{ number_format($tax['subtotal'], 2) }}</span>
                            </li>
                            @foreach ($tax['tax_lines'] as $taxLine)
                                <li class="flex justify-between gap-4 text-gray-500 dark:text-gray-400">
                                    <span>{{ $taxLine['title'] }} ({{ rtrim(rtrim(number_format($taxLine['rate'], 3), '0'), '.') }}%)</span>
                                    <span class="shrink-0">${{ number_format($taxLine['amount'], 2) }}</span>
                                </li>
                            @endforeach
                            <li class="flex justify-between gap-4 font-semibold text-gray-900 dark:text-gray-100 border-t border-gray-100 dark:border-gray-700 pt-1 mt-1">
                                <span>Total (calculated)</span>
                                <span class="shrink-0">${{ number_format($calculatedPrice, 2) }}</span>
                            </li>
                        </ul>
                    </div>
                @endif

                <form method="POST" id="quote-review-form" class="mt-6 text-left space-y-4">
                    @csrf
                    <input type="hidden" name="answers" value="{{ $answersJson }}">

                    <div>
                        <x-input-label value="Customer" />
                        <div class="mt-1 flex rounded-lg shadow-sm">
                            <button type="button" @click="customerMode = 'new'; clearCustomer()"
                                class="flex-1 px-3 py-2 text-sm font-medium rounded-l-lg border"
                                :class="customerMode === 'new' ? 'brand-bg text-white border-transparent' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'">
                                New Customer
                            </button>
                            <button type="button" @click="customerMode = 'existing'"
                                class="flex-1 px-3 py-2 text-sm font-medium rounded-r-lg border-t border-b border-r"
                                :class="customerMode === 'existing' ? 'brand-bg text-white border-transparent' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'">
                                Existing Customer
                            </button>
                        </div>
                    </div>

                    <template x-if="customerMode === 'new'">
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="customer_name" value="Customer Name" />
                                <x-text-input id="customer_name" name="customer_name" type="text" class="block mt-1 w-full" required />
                                <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="customer_email" value="Customer Email" />
                                <x-text-input id="customer_email" name="customer_email" type="email" class="block mt-1 w-full" required />
                                <x-input-error :messages="$errors->get('customer_email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="customer_phone" value="Customer Phone (optional)" />
                                <x-text-input id="customer_phone" name="customer_phone" type="text" class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
                            </div>
                        </div>
                    </template>

                    <template x-if="customerMode === 'existing'">
                        <div class="relative">
                            <x-input-label value="Search Past Customers" />
                            <input type="text" x-model="customerSearch" @input="selectedCustomer = null" autocomplete="off"
                                placeholder="Type a name or email…"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2.5">

                            <div x-show="!selectedCustomer && filteredCustomers.length > 0" x-cloak
                                class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                <template x-for="customer in filteredCustomers" :key="customer.email">
                                    <button type="button" @click="pickCustomer(customer)"
                                        class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <span class="block text-gray-900 dark:text-gray-100" x-text="customer.name"></span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="customer.email"></span>
                                    </button>
                                </template>
                            </div>

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="selectedCustomer">
                                Selected — <button type="button" class="brand-text font-medium" @click="clearCustomer()">change</button>
                            </p>

                            <input type="hidden" name="customer_name" :value="selectedCustomer ? selectedCustomer.name : ''">
                            <input type="hidden" name="customer_email" :value="selectedCustomer ? selectedCustomer.email : ''">
                            <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                            <x-input-error :messages="$errors->get('customer_email')" class="mt-2" />
                        </div>
                    </template>

                    <div>
                        <x-input-label for="internal_notes" value="Internal Notes" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Staff-only — never shown to the customer.</p>
                        <textarea id="internal_notes" name="internal_notes" rows="3"
                            class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm"></textarea>
                        <x-input-error :messages="$errors->get('internal_notes')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Prepared By" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Shown on the quote and PDF — defaults to you, but you can put a different name/email here.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-text-input name="prepared_by_name" type="text" class="block w-full" value="{{ old('prepared_by_name', $preparedByName) }}" required />
                                <x-input-error :messages="$errors->get('prepared_by_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-text-input name="prepared_by_email" type="email" class="block w-full" value="{{ old('prepared_by_email', $preparedByEmail) }}" required />
                                <x-input-error :messages="$errors->get('prepared_by_email')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="expires_in_days" value="Quote Expiration (optional)" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Shown on the quote and PDF as a "valid until" date.</p>
                        <select id="expires_in_days" name="expires_in_days"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5">
                            <option value="">No expiration</option>
                            @foreach (\App\Models\Quote::EXPIRATION_DAY_OPTIONS as $days)
                                <option value="{{ $days }}">{{ $days }} days</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('expires_in_days')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Discount (optional)" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Comes off before tax — tax is recalculated on the discounted amount.</p>
                        <div class="flex gap-2">
                            <select name="discount_type" x-model="discountType"
                                class="w-32 shrink-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5">
                                <option value="">None</option>
                                <option value="fixed">$ off</option>
                                <option value="percentage">% off</option>
                            </select>
                            <input name="discount_value" type="number" step="0.01" min="0" x-model="discountValue" :disabled="! discountType"
                                placeholder="{{ '0' }}"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 disabled:opacity-50">
                        </div>
                        <x-input-error :messages="$errors->get('discount_type')" class="mt-2" />
                        <x-input-error :messages="$errors->get('discount_value')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="override_display" value="Override Price (optional)" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Types the exact final price instead — takes priority over any discount above.</p>
                        <input id="override_display" name="price_override" type="number" step="0.01" min="0" x-model="override"
                            placeholder="{{ number_format($calculatedPrice, 2) }}"
                            class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2.5">
                        <x-input-error :messages="$errors->get('price_override')" class="mt-2" />
                    </div>

                    <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
                        Nothing has been saved yet — pick what to do with this quote below.
                    </p>

                    <div class="space-y-2">
                        <button type="submit" formaction="{{ route('quotes.create.save', $product) }}"
                            class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 brand-bg rounded-lg font-semibold text-sm text-white transition">
                            Save Quote
                        </button>
                        <button type="submit" formaction="{{ route('quotes.create.email', $product) }}"
                            class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Save &amp; Email to Customer
                        </button>
                        <button type="submit" formaction="{{ route('quotes.create.pdf', $product) }}"
                            class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Save &amp; Download PDF
                        </button>
                    </div>
                </form>

                <div class="mt-4 flex items-center justify-center gap-4 text-sm">
                    <a href="{{ route('quotes.create.show', $product) }}" class="font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">&larr; Back to edit</a>
                </div>
            </div>
        </x-card>
        </div>
    </div>
    </div>
</x-app-layout>
