<!DOCTYPE html>
<html lang="en" class="{{ request('theme') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Get a Quote — {{ $business->name }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>:root { --brand-color: {{ $business->brand_color ?? '#4f46e5' }}; }</style>
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <div class="min-h-screen py-8 px-4 sm:py-12">
            <div class="max-w-xl mx-auto">
                <div class="text-center mb-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $business->name }}</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">What would you like a quote for?</h1>
                </div>

                @if ($products->isEmpty())
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-8 text-center text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                        No quote forms are available right now — please check back soon.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($products as $product)
                            <a href="{{ route('quote.show', [$business, $product]) }}{{ request('theme') === 'dark' ? '?theme=dark' : '' }}"
                                class="flex items-center justify-between gap-4 bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:shadow-md brand-hover-border transition dark:bg-gray-800 dark:border-gray-700">
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
                                    Get a Quote
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @include('public._powered-by')
    </body>
</html>
