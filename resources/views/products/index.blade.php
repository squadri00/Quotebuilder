<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Products</h2>
            <x-primary-button onclick="window.location='{{ route('products.create') }}'">New Product</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    @if ($errors->has('display_conditions'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-800">Can't publish yet</p>
            <p class="text-xs text-red-700 mt-0.5">{{ $errors->first('display_conditions') }}</p>
        </div>
    @endif

    @php
        $business = Auth::user()->business;
        $hubEligible = $products->filter(fn ($p) => $p->is_active && $p->published_snapshot)
            ->sortBy([fn ($a, $b) => ($a->quote_hub_sort_order ?? 999) <=> ($b->quote_hub_sort_order ?? 999), fn ($a, $b) => $a->name <=> $b->name]);
        $hubIneligibleCount = $products->count() - $hubEligible->count();
    @endphp

    <x-card class="mb-6">
        <div x-data="{ open: {{ old('products') ? 'true' : 'false' }}, copied: null }">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Quote Hub — show several calculators on one page</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pick which products appear on a single "choose a service" page, so a customer can pick which one they want a quote for. Great for embedding more than one calculator on your website at once.</p>
                </div>
                <x-secondary-button type="button" @click="open = !open" x-text="open ? 'Hide' : 'Manage Quote Hub'">Manage Quote Hub</x-secondary-button>
            </div>

            <div x-show="open" x-cloak class="mt-5 space-y-5">
                @if ($hubEligible->isEmpty())
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        No products are eligible yet — a product needs to be Active and Published before it can join the Quote Hub.
                    </p>
                @else
                    <form method="POST" action="{{ route('quote-hub.update') }}">
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

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-5">
                        <p class="font-medium text-sm text-gray-900 dark:text-gray-100">Embed the Quote Hub on your website</p>
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
                @endif
            </div>
        </div>
    </x-card>

    @if ($products->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No products yet. Create your first one to get started.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($products as $product)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</p>
                            @if ($product->is_active)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Inactive</span>
                            @endif
                            @if ($product->hasUnpublishedChanges())
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">Draft changes</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Published</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Base price ${{ number_format($product->base_price, 2) }} &middot; {{ $product->questions->count() }} question(s)</p>
                    </div>
                    <div class="flex items-center gap-4">
                        @if ($product->hasUnpublishedChanges())
                            <form method="POST" action="{{ route('products.publish', $product) }}">
                                @csrf
                                <button type="submit" class="text-sm font-medium brand-text">Publish</button>
                            </form>
                        @endif
                        <a href="{{ route('products.questions.index', $product) }}" class="text-sm font-medium brand-text">Questions</a>
                        <a href="{{ route('products.edit', $product) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900">Edit</a>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
