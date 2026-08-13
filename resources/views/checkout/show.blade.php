<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Complete Your Subscription — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        @include('checkout._iframe-handoff')

        <div class="min-h-screen flex flex-col">
            <header class="border-b border-gray-200 bg-white">
                <div class="max-w-xl mx-auto px-6 h-16 flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">{{ Str::upper(Str::substr(config('app.name'), 0, 1)) }}</span>
                        <span class="font-bold text-gray-900">{{ config('app.name') }}</span>
                    </a>
                </div>
            </header>

            <main class="flex-1">
                <div class="max-w-xl mx-auto px-6 py-12">
                    @include('partials.checkout-content', ['plan' => $plan, 'pending' => $pending, 'tax' => $tax])
                </div>
            </main>
        </div>
    </body>
</html>
