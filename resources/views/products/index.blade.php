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
