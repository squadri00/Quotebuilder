<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Quote</h2>
    </x-slot>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Pick which product you're quoting for the customer.</p>

    @if ($products->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            You don't have any quotable products yet.
        </x-card>
    @else
        <div class="space-y-3">
            @foreach ($products as $product)
                <a href="{{ route('quotes.create.show', $product) }}"
                    class="flex items-center justify-between gap-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 hover:shadow-md brand-hover-border transition">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</p>
                        @if ($product->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $product->description }}</p>
                        @endif
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Starting at ${{ number_format($product->published_snapshot['product']['base_price'] ?? 0, 2) }}
                        </p>
                    </div>
                    <span class="shrink-0 inline-flex items-center justify-center px-4 py-2 brand-bg rounded-lg text-sm font-semibold text-white">
                        Start Quote
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
