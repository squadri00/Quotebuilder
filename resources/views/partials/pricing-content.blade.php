<div class="max-w-6xl mx-auto px-6 py-16" x-data="{ interval: 'monthly' }">
    <div class="text-center max-w-2xl mx-auto" data-aos="fade-up" data-aos-once="true">
        <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-sm font-semibold">Simple, transparent pricing</div>
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100">Pick the plan that fits your business</h1>
        <p class="mt-3 text-lg text-gray-500 dark:text-gray-400">Upgrade or cancel any time.</p>
    </div>

    @if ($tiers->isEmpty())
        <p class="mt-12 text-center text-gray-500 dark:text-gray-400">Pricing isn't available right now — please check back soon.</p>
    @else
        <!-- Monthly / Yearly toggle -->
        <div class="mt-10 flex justify-center">
            <div class="inline-flex items-center gap-6 rounded-full border-2 border-green-200 dark:border-green-800 bg-white dark:bg-gray-800 px-6 py-3 shadow-md" role="radiogroup" aria-label="Billing interval">
                <button type="button" @click="interval = 'monthly'"
                    role="radio" :aria-checked="interval === 'monthly'"
                    class="flex items-center gap-2 text-base font-semibold transition"
                    :class="interval === 'monthly' ? 'text-green-600 dark:text-green-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                        :class="interval === 'monthly' ? 'border-green-600 dark:border-green-400' : 'border-gray-300 dark:border-gray-600'">
                        <span class="h-2.5 w-2.5 rounded-full bg-green-600 dark:bg-green-400" x-show="interval === 'monthly'"></span>
                    </span>
                    Monthly
                </button>

                <button type="button" @click="interval = 'yearly'"
                    role="radio" :aria-checked="interval === 'yearly'"
                    class="flex items-center gap-2 text-base font-semibold transition"
                    :class="interval === 'yearly' ? 'text-green-600 dark:text-green-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                        :class="interval === 'yearly' ? 'border-green-600 dark:border-green-400' : 'border-gray-300 dark:border-gray-600'">
                        <span class="h-2.5 w-2.5 rounded-full bg-green-600 dark:bg-green-400" x-show="interval === 'yearly'"></span>
                    </span>
                    Yearly
                </button>

                <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-3 py-1 text-sm font-bold text-green-700 dark:text-green-300">
                    Save 2 months
                </span>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-start">
            @foreach ($tiers as $tier)
                @php
                    $monthly = $tier['monthly'];
                    $yearly = $tier['yearly'];
                    $anyVariant = $monthly ?? $yearly;
                @endphp
                <div
                    x-data="{
                        monthly: @js($monthly ? [
                            'id' => $monthly->id,
                            'price' => (float) $monthly->price,
                            'bullets' => $monthly->marketingBulletsList(),
                            'hasDiscount' => $monthly->hasDiscount(),
                            'comparePrice' => (float) $monthly->compare_at_price,
                            'discountAmount' => $monthly->discountAmount(),
                            'discountPercent' => $monthly->discountPercent(),
                            'discountDisplay' => $monthly->discount_display,
                        ] : null),
                        yearly: @js($yearly ? [
                            'id' => $yearly->id,
                            'price' => (float) $yearly->price,
                            'bullets' => $yearly->marketingBulletsList(),
                            'hasDiscount' => $yearly->hasDiscount(),
                            'comparePrice' => (float) $yearly->compare_at_price,
                            'discountAmount' => $yearly->discountAmount(),
                            'discountPercent' => $yearly->discountPercent(),
                            'discountDisplay' => $yearly->discount_display,
                        ] : null),
                        get plan() { return interval === 'yearly' && this.yearly ? this.yearly : this.monthly; },
                        fxRates: @js($fxRates),
                        currencySymbols: @js($currencySymbols),
                        masterSymbol: @js($masterSymbol),
                        masterSymbolAfter: @js($masterSymbolAfter),
                        fmt(amount) {
                            const n = Math.round(amount);
                            return this.masterSymbolAfter ? (n + this.masterSymbol) : (this.masterSymbol + n);
                        },
                        get converted() {
                            return Object.entries(this.fxRates).map(([code, rate]) => ({
                                code,
                                symbol: this.currencySymbols[code] ?? code,
                                amount: Math.round(this.plan.price * rate),
                            }));
                        },
                    }"
                    class="relative rounded-2xl border bg-white dark:bg-gray-800 p-8 flex flex-col h-full {{ $anyVariant->is_highlighted ? 'border-green-600 dark:border-green-500 shadow-lg ring-1 ring-green-600 dark:ring-green-500' : 'border-gray-200 dark:border-gray-700 shadow-sm' }}"
                    data-aos="fade-up" data-aos-once="true"
                >
                    @if ($anyVariant->is_highlighted)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white">
                            Most Popular
                        </span>
                    @endif

                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $anyVariant->name }}</h2>

                    <div class="mt-2">
                        <p class="text-sm text-gray-400 dark:text-gray-500 line-through" x-show="plan.hasDiscount" x-cloak x-text="fmt(plan.comparePrice)"></p>
                        <p class="flex items-baseline gap-1">
                            <span class="text-4xl font-bold text-gray-900 dark:text-gray-100" x-text="fmt(plan.price)"></span>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400" x-text="interval === 'yearly' ? '/yr' : '/mo'"></span>
                        </p>
                        <span
                            class="inline-flex items-center mt-1.5 rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-bold text-green-700 dark:text-green-300"
                            x-show="plan.hasDiscount"
                            x-cloak
                            x-text="plan.discountDisplay === 'fixed' ? 'Save ' + fmt(plan.discountAmount) : 'Save ' + plan.discountPercent + '%'"
                        ></span>

                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" x-show="converted.length && plan.price > 0" x-cloak>
                            &asymp;
                            <template x-for="(c, i) in converted" :key="c.code">
                                <span x-text="c.symbol + c.amount + ' ' + c.code + (i < converted.length - 1 ? ' &middot; ' : '')"></span>
                            </template>
                        </p>
                    </div>

                    <ul class="mt-6 space-y-3 text-sm text-gray-700 dark:text-gray-300 flex-1">
                        <template x-for="bullet in plan.bullets" :key="bullet">
                            <li class="flex items-start gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 shrink-0 text-green-600 dark:text-green-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span x-text="bullet"></span>
                            </li>
                        </template>
                    </ul>

                    <a :href="'{{ $registerUrl ?? route('register') }}?plan=' + plan.id" {!! $linkTarget ?? '' !!}
                        class="mt-8 inline-flex items-center justify-center w-full px-4 py-2.5 rounded-lg text-sm font-semibold transition {{ $anyVariant->is_highlighted ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        Sign Up
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
