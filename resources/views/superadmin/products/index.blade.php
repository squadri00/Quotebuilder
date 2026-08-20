<x-superadmin-layout :title="$business->name.' — Products'">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('superadmin.businesses.show', $business) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $business->name }}</a> / Quote Builder
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Products</h2>
            </div>
            <div class="flex items-center gap-2">
                @if ($business->is_template)
                    <x-secondary-button onclick="window.location='{{ route('superadmin.quotes.create.products', $business) }}'">Demo Quote</x-secondary-button>
                @endif
                <x-primary-button onclick="window.location='{{ route('superadmin.products.create', $business) }}'">New Product</x-primary-button>
            </div>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->has('display_conditions'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-800">Can't publish yet</p>
            <p class="text-xs text-red-700 mt-0.5">{{ $errors->first('display_conditions') }}</p>
        </div>
    @endif

    @php
        $hubEligible = $products->filter(fn ($p) => $p->is_active && $p->published_snapshot)
            ->sortBy([fn ($a, $b) => ($a->quote_hub_sort_order ?? 999) <=> ($b->quote_hub_sort_order ?? 999), fn ($a, $b) => $a->name <=> $b->name]);
        $hubIneligibleCount = $products->count() - $hubEligible->count();
    @endphp

    <x-card class="mb-6">
        <div x-data="{ open: {{ old('products') ? 'true' : 'false' }}, copied: null }">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Quote Hub — show several calculators on one page</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pick which of {{ $business->name }}'s products appear on a single "choose a service" page — this is what the public Demo page embeds for this industry.</p>
                </div>
                <x-secondary-button type="button" @click="open = !open" x-text="open ? 'Hide' : 'Manage Quote Hub'">Manage Quote Hub</x-secondary-button>
            </div>

            <div x-show="open" x-cloak class="mt-5 space-y-5">
                @if ($hubEligible->isEmpty())
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        No products are eligible yet — a product needs to be Active and Published before it can join the Quote Hub.
                    </p>
                @else
                    <form method="POST" action="{{ route('superadmin.quote-hub.update', $business) }}">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-2">
                            @foreach ($hubEligible as $product)
                                <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
                                    <label class="flex items-center gap-2 flex-1 min-w-0 cursor-pointer">
                                        <input type="checkbox" name="products[{{ $product->id }}][show]" value="1"
                                            @checked($product->show_in_quote_hub)
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                                        <span class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $product->name }}</span>
                                    </label>
                                    <label class="flex items-center gap-2 shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                        Order
                                        <input type="number" name="products[{{ $product->id }}][order]" min="1"
                                            value="{{ $product->quote_hub_sort_order ?? '' }}"
                                            class="w-16 text-sm border-gray-300 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @if ($hubIneligibleCount > 0)
                            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                                {{ $hubIneligibleCount }} other product(s) aren't eligible yet (inactive or not yet published).
                            </p>
                        @endif

                        <x-primary-button type="submit" class="mt-4">Save Quote Hub</x-primary-button>
                    </form>
                @endif

                <div class="border-t border-gray-100 dark:border-gray-700 pt-5">
                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100">Embed the Quote Hub on a website</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Paste this once, instead of a separate embed snippet per product.</p>
                    <textarea x-ref="hubSnippet" readonly rows="2" @click="$event.target.select()"
                        class="mt-2 w-full font-mono text-xs border-gray-300 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 dark:text-gray-300 resize-none">{{ '<script src="'.url('/embed.js').'" data-business="'.$business->slug.'"></script>' }}</textarea>
                    <button type="button"
                        @click="navigator.clipboard.writeText($refs.hubSnippet.value); copied = 'snippet'; setTimeout(() => copied = null, 2000)"
                        class="mt-2 inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <span x-text="copied === 'snippet' ? 'Copied!' : 'Copy to Clipboard'">Copy to Clipboard</span>
                    </button>

                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100 mt-5">Or link straight to it</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">No website needed — share this link directly (works great from a QR code too).</p>
                    <textarea x-ref="hubLink" readonly rows="1" @click="$event.target.select()"
                        class="mt-2 w-full font-mono text-xs border-gray-300 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 dark:text-gray-300 resize-none">{{ route('quote.picker', $business) }}</textarea>
                    <button type="button"
                        @click="navigator.clipboard.writeText($refs.hubLink.value); copied = 'link'; setTimeout(() => copied = null, 2000)"
                        class="mt-2 inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <span x-text="copied === 'link' ? 'Copied!' : 'Copy to Clipboard'">Copy to Clipboard</span>
                    </button>
                </div>
            </div>
        </div>
    </x-card>

    @if ($products->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No products yet for {{ $business->name }}. Create the first one to get started.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($products as $product)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</p>
                            @if ($product->is_active)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Inactive</span>
                            @endif
                            @if ($product->hasUnpublishedChanges())
                                <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">Draft changes</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Published</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Base price ${{ number_format($product->base_price, 2) }} &middot; {{ $product->questions->count() }} question(s) &middot; {{ $product->rules->count() }} rule(s)</p>
                    </div>
                    <div class="flex items-center gap-4">
                        @if ($product->hasUnpublishedChanges())
                            <form method="POST" action="{{ route('superadmin.products.publish', [$business, $product]) }}">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Publish</button>
                            </form>
                        @endif
                        @if ($product->published_snapshot)
                            <a href="{{ route('superadmin.products.preview', [$business, $product]) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                Preview
                            </a>
                        @endif
                        <a href="{{ route('superadmin.products.questions.index', [$business, $product]) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Questions</a>
                        <a href="{{ route('superadmin.rules.index', $business) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Rules</a>
                        <a href="{{ route('superadmin.products.edit', [$business, $product]) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
