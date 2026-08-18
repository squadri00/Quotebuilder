<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>{{ $platformSettings->platform_name ?: config('app.name') }} — Temporarily Unavailable</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-6 bg-gray-50">
            <x-platform-logo :settings="$platformSettings" img-class="h-14 w-14 shrink-0 rounded-lg object-contain mb-6" />

            <div class="w-full max-w-md text-center bg-white border border-gray-200 shadow-sm rounded-xl px-8 py-10">
                <h1 class="text-xl font-bold text-gray-900 mb-3">We'll be right back</h1>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $platformSettings->maintenanceMessageOrDefault() }}</p>
            </div>
        </div>
    </body>
</html>
