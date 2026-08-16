<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Runwrk'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-gray-900">
        <header class="bg-white border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between gap-6">
                <a href="{{ url('/') }}" class="flex items-center shrink-0">
                    <img src="{{ asset('images/runwrk-logo.webp') }}" alt="{{ config('app.name', 'Runwrk') }}" class="h-7 w-auto">
                </a>

                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ url('/#features') }}" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-indigo-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4">
                            <circle cx="12" cy="12" r="9" />
                            <path stroke-linecap="round" d="M9 10h.01M15 10h.01M8.5 14.5c.9.9 2.1 1.5 3.5 1.5s2.6-.6 3.5-1.5" />
                        </svg>
                        Features
                    </a>
                    <a href="{{ route('pricing') }}" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-indigo-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5M12 19.5V21M6.5 6.5A5.5 5.5 0 0112 3a5.5 5.5 0 015.5 3.5c0 3-2.5 3.8-3.8 5-.9.85-1.2 1.5-1.2 2.5h-3c0-1.6.6-2.6 1.8-3.7 1-.95 2.2-1.6 2.2-3.1a2.5 2.5 0 00-5 0" />
                            <path stroke-linecap="round" d="M10.5 17.5h3" />
                        </svg>
                        Pricing
                    </a>
                    <a href="mailto:{{ optional($platformSettings ?? null)->contact_email ?: 'hello@runwrk.com' }}" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-indigo-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4">
                            <circle cx="12" cy="12" r="9" />
                            <path stroke-linecap="round" d="M3 12h18M12 3c2.2 2.4 3.4 5.6 3.4 9s-1.2 6.6-3.4 9c-2.2-2.4-3.4-5.6-3.4-9S9.8 5.4 12 3z" />
                        </svg>
                        Contact
                    </a>
                    <a href="{{ route('login') }}" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-indigo-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4">
                            <circle cx="12" cy="8" r="3.25" />
                            <path stroke-linecap="round" d="M5.5 20c1.4-3.2 4-4.8 6.5-4.8s5.1 1.6 6.5 4.8" />
                        </svg>
                        Login
                    </a>
                </nav>

                <a href="tel:+18667987860" class="hidden sm:inline-flex items-center rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition shrink-0">
                    +1 (866) 798-7860
                </a>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="mt-24 bg-indigo-950 text-white">
            <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                <p class="text-sm text-indigo-200">© {{ config('app.name', 'Runwrk') }}.com by {{ optional($platformSettings ?? null)->legal_business_name ?: 'Eformics Systems' }} - 2009 - {{ date('Y') }} All Rights Reserved</p>
                <div class="flex items-center gap-6 text-sm text-indigo-200">
                    <a href="#" class="hover:text-white transition">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition">Terms of Use</a>
                </div>
            </div>
        </footer>
    </body>
</html>
