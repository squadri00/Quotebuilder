<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Templates</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    @php
        $limit = $business->productLimit();
        // Only active products count against the limit — see
        // Business::hasReachedProductLimit()'s docblock — so this has to
        // match that same counting rule, or this page would show "at your
        // limit" while the actual check underneath disagrees.
        $activeProductCount = $myProducts->where('is_active', true)->count();
        $atLimit = $limit !== null && $activeProductCount >= $limit;
    @endphp

    <x-card class="mb-6">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Your Industry</p>
        @if ($business->industry)
            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $business->industry->name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Showing template products for this industry. Only we can change your assigned industry — contact us if it needs to change.
            </p>
        @else
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                No industry is assigned to your account yet — contact us to get one set so we can show you matching templates.
            </p>
        @endif

        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">
            {{ $activeProductCount }} of {{ $limit === null ? 'unlimited' : $limit }} active product(s) used.
            @if ($atLimit)
                You're at your plan's limit — deactivate one to make room without losing it, replace one below, or upgrade your plan for more room.
            @endif
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
            Adding a product below copies it into your own account, once — it's yours to edit from there. If we improve the original template later, it won't change what you've already added.
        </p>
    </x-card>

    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Available Templates</h3>

    @if ($availableProducts->isEmpty())
        <x-card class="mb-6 text-center text-gray-500 dark:text-gray-400">
            @if ($business->industry)
                No template products are available for your industry right now.
            @else
                Nothing to show until your account has an industry assigned.
            @endif
        </x-card>
    @else
        <div class="space-y-4 mb-6">
            @foreach ($availableProducts as $product)
                <x-card class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Base price ${{ number_format($product->base_price, 2) }} &middot; {{ $product->questions->count() }} question(s)
                        </p>
                    </div>

                    @if (in_array($product->id, $installedTemplateProductIds, true))
                        <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">Already added</span>
                    @elseif (! $atLimit)
                        <form method="POST" action="{{ route('templates.store', $product->id) }}">
                            @csrf
                            <x-secondary-button type="submit">Add</x-secondary-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('templates.store', $product->id) }}"
                            class="flex items-center gap-2"
                            onsubmit="return confirm('Replace the selected product with &quot;{{ $product->name }}&quot;? This also deletes its questions and options.');">
                            @csrf
                            <select name="replace_product_id" required
                                class="text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                <option value="" disabled selected>Replace which product?</option>
                                @foreach ($myProducts as $mine)
                                    <option value="{{ $mine->id }}">{{ $mine->name }}</option>
                                @endforeach
                            </select>
                            <x-secondary-button type="submit">Replace</x-secondary-button>
                        </form>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif

    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Your Products</h3>

    @if ($myProducts->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No products yet — add one from the templates above, or create one from scratch on the Products page.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($myProducts as $product)
                <x-card class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Base price ${{ number_format($product->base_price, 2) }} &middot; {{ $product->questions->count() }} question(s)
                        </p>
                    </div>

                    <form method="POST" action="{{ route('products.destroy', $product) }}"
                        onsubmit="return confirm('Remove this product? This also deletes its questions and options.');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">Remove</x-danger-button>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
