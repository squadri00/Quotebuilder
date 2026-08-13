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
