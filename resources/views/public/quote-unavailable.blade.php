<!DOCTYPE html>
<html lang="en" class="{{ request('theme') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Quotes Unavailable — {{ $business->name }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>:root { --brand-color: {{ $business->brand_color ?? '#4f46e5' }}; }</style>
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <div class="min-h-[600px] flex items-center justify-center px-4 py-12">
            <div class="max-w-md w-full bg-white border border-gray-200 rounded-xl shadow-sm p-8 text-center dark:bg-gray-800 dark:border-gray-700">
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $business->name }} — {{ $product->name }}</p>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                    This business isn't able to accept new quote requests right now. Please check back soon or contact them directly.
                </p>
            </div>
        </div>

        @include('public._powered-by')
        @include('public._embed-resize')
    </body>
</html>
