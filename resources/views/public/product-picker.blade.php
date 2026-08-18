<!DOCTYPE html>
<html lang="en" class="{{ request('theme') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $ogTitle = 'Get a Quote — '.$business->name;
            $ogDescription = "Request a free, instant quote from {$business->name}.";
            $ogImage = $business->logo_path ? Storage::url($business->logo_path) : null;
        @endphp

        <title>{{ $ogTitle }}</title>
        <meta name="description" content="{{ $ogDescription }}">

        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
        @endif

        <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        @if ($ogImage)
            <meta name="twitter:image" content="{{ $ogImage }}">
        @endif

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>:root { --brand-color: {{ $business->brand_color ?? '#4f46e5' }}; }</style>
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <div class="py-8 px-4 sm:py-12">
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
                            @php
                                // ~20 seconds per question is a reasonable ballpark for a
                                // single-choice wizard — rounded up to the nearest minute
                                // so a 2-question product still reads as "about 1 min"
                                // rather than "about 0 min."
                                $questionCount = count($product->published_snapshot['questions'] ?? []);
                                $estimatedMinutes = max(1, (int) ceil($questionCount * 20 / 60));
                            @endphp
                            <a href="{{ route('quote.show', [$business, $product]) }}{{ request('theme') === 'dark' ? '?theme=dark' : '' }}"
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:shadow-md brand-hover-border transition dark:bg-gray-800 dark:border-gray-700 brand-focus-ring">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</p>
                                    @if ($product->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $product->description }}</p>
                                    @endif
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Starting at ${{ number_format($product->published_snapshot['product']['base_price'] ?? 0, 2) }}
                                        @if ($questionCount > 0)
                                            &middot; Takes about {{ $estimatedMinutes }} min
                                        @endif
                                    </p>
                                </div>
                                <span class="shrink-0 inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 brand-bg rounded-lg text-sm font-semibold text-white">
                                    Get a Quote
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @include('public._powered-by')
        @include('public._embed-resize')
    </body>
</html>
