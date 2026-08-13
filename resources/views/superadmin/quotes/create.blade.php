<x-superadmin-layout :title="'Demo Quote — '.$business->name">
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('superadmin.quotes.create') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Demo Quote</a> / {{ $business->name }}
            </p>
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $business->name }}</h2>
        </div>
    </x-slot>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Pick a product to run through the wizard. This is a real, fully-working quote (real save, real email, real PDF) —
        it just never touches a real business's account or their Quote Inbox.
    </p>

    @if ($products->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            {{ $business->name }} has no quotable products yet — go to
            <a href="{{ route('superadmin.products.index', $business) }}" class="font-medium text-indigo-600 dark:text-indigo-400">its Products page</a>
            and publish one.
        </x-card>
    @else
        <div class="space-y-3">
            @foreach ($products as $product)
                <a href="{{ route('superadmin.quotes.create.show', $product) }}"
                    class="flex items-center justify-between gap-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 hover:shadow-md transition">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</p>
                        @if ($product->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $product->description }}</p>
                        @endif
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Starting at ${{ number_format($product->published_snapshot['product']['base_price'] ?? 0, 2) }}
                        </p>
                    </div>
                    <span class="shrink-0 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 rounded-lg text-sm font-semibold text-white">
                        Start Quote
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
