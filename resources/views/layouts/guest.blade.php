<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/quotaire/favicon.png') }}?v={{ @filemtime(public_path('images/quotaire/favicon.png')) }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @php
            $platformSettings = \App\Models\PlatformSetting::get();
            $logoFull = $platformSettings->logo_path && $platformSettings->logo_display_style === 'full';
        @endphp
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50">
            <div>
                <a href="/" class="flex items-center gap-2">
                    <x-platform-logo :settings="$platformSettings" :img-class="$logoFull ? 'h-10 w-auto max-w-[220px] object-contain' : 'h-10 w-10 shrink-0 rounded-lg object-contain'" />
                    @unless ($logoFull)
                        <span class="text-xl font-bold text-gray-900">{{ $platformSettings->platform_name ?: config('app.name') }}</span>
                    @endunless
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white border border-gray-200 shadow-sm overflow-hidden sm:rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
