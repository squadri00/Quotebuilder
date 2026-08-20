<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{--
            Runs before anything paints, so the page never flashes the wrong
            theme. Visitors here aren't logged in (no user row to persist a
            preference to like the dashboard does), so this reads
            localStorage instead, falling back to the OS-level preference
            the very first time someone visits.
        --}}
        <script>
            if (localStorage.theme === 'dark' || (! ('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <title>@yield('title', config('app.name', 'Quotaire') . ' — The Quotation Builder')</title>

        <link rel="icon" type="image/png" href="{{ asset('images/quotaire/favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if (($platformSettings ?? null)?->turnstile_site_key)
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif

        <style>
            html { scroll-behavior: smooth; }
        </style>
    </head>
    <body class="font-display antialiased bg-white text-gray-700 dark:bg-gray-900 dark:text-gray-300">
        <div x-data="{ open: false }" class="w-full">
            <div class="flex flex-col max-w-6xl mx-auto px-6 md:items-center md:justify-between md:flex-row">
                <div class="flex flex-row items-center justify-between py-6">
                    <a href="{{ url('/') }}" class="relative z-50 flex items-center shrink-0">
                        <img src="{{ asset('images/quotaire/logo-for-white.png') }}" alt="{{ config('app.name', 'Quotaire') }}" class="h-8 w-auto dark:hidden">
                        <img src="{{ asset('images/quotaire/logo-for-black.png') }}" alt="{{ config('app.name', 'Quotaire') }}" class="h-8 w-auto hidden dark:block">
                    </a>

                    <button class="rounded-lg md:hidden focus:outline-none focus:shadow-outline" @click="open = !open" aria-label="Toggle navigation">
                        <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6 text-gray-700 dark:text-gray-300">
                            <path x-show="!open" fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            <path x-show="open" x-cloak fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>

                <nav :class="{ 'transform md:transform-none': !open, 'h-full': open }" class="h-0 md:h-auto overflow-hidden flex flex-col flex-grow md:items-center pb-4 md:pb-0 md:overflow-visible md:flex md:justify-end md:flex-row origin-top duration-300 scale-y-0 md:scale-y-100">
                    <a href="{{ url('/') }}" class="px-4 py-2 mt-2 text-base font-semibold {{ request()->is('/') ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100' }} rounded-lg md:ml-4 hover:text-green-600 dark:hover:text-green-400 focus:outline-none focus:shadow-outline">Home</a>
                    <a href="{{ route('features') }}" class="px-4 py-2 mt-2 text-base font-semibold {{ request()->routeIs('features') ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100' }} rounded-lg md:ml-4 hover:text-green-600 dark:hover:text-green-400 focus:outline-none focus:shadow-outline">Features</a>
                    <a href="{{ route('pricing') }}" class="px-4 py-2 mt-2 text-base font-semibold {{ request()->routeIs('pricing') ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100' }} rounded-lg md:ml-4 hover:text-green-600 dark:hover:text-green-400 focus:outline-none focus:shadow-outline">Pricing</a>
                    <a href="{{ route('demo') }}" class="px-4 py-2 mt-2 text-base font-semibold {{ request()->routeIs('demo') ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100' }} rounded-lg md:ml-4 hover:text-green-600 dark:hover:text-green-400 focus:outline-none focus:shadow-outline">Demo</a>
                    <a href="{{ route('contact') }}" class="px-4 py-2 mt-2 text-base font-semibold {{ request()->routeIs('contact') ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100' }} rounded-lg md:ml-4 hover:text-green-600 dark:hover:text-green-400 focus:outline-none focus:shadow-outline">Contact</a>

                    <button
                        type="button"
                        x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = ! dark; document.documentElement.classList.toggle('dark', dark); localStorage.theme = dark ? 'dark' : 'light';"
                        class="mt-2 md:ml-4 flex items-center justify-center w-10 h-10 rounded-full text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-100 dark:hover:bg-gray-800 transition"
                        aria-label="Toggle dark mode">
                        <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-10 py-3 mt-2 text-sm text-center font-semibold bg-green-600 text-white rounded-full md:ml-4 hover:bg-green-700 transition">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-10 py-3 mt-2 text-sm text-center font-semibold bg-gray-100 text-gray-800 rounded-full md:ml-4 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 transition">Login</a>
                        <a href="{{ route('pricing') }}" class="px-10 py-3 mt-2 text-sm text-center font-semibold bg-green-600 text-white rounded-full md:ml-4 hover:bg-green-700 transition">Sign Up</a>
                    @endauth
                </nav>
            </div>
        </div>

        <main>
            @yield('content')
        </main>

        <footer class="mt-24" style="background-color: #2f327d;">
            <div class="max-w-6xl mx-auto px-6">
                <div class="flex flex-col items-center py-12 text-white">
                    <img src="{{ asset('images/quotaire/logo-for-black.png') }}" alt="{{ config('app.name', 'Quotaire') }}" class="h-7 w-auto">
                    <p class="mt-4 text-sm text-indigo-200">The quote calculator builder for businesses that quote by products, options, and rules.</p>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-between text-gray-300 text-sm py-6 border-t border-white/10">
                    <div class="mb-4 md:mb-0 text-center md:text-left">
                        © {{ config('app.name', 'Quotaire') }}.com by {{ optional($platformSettings ?? null)->legal_business_name ?: 'Eformics Systems' }} - 2009 - {{ date('Y') }} All Rights Reserved
                    </div>
                    <div class="flex items-center gap-6">
                        <a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a>
                        <a href="{{ route('legal.show', 'privacy-policy') }}" class="hover:text-white transition">Privacy Policy</a>
                        <a href="{{ route('legal.show', 'terms-of-use') }}" class="hover:text-white transition">Terms of Use</a>
                    </div>
                </div>
            </div>
        </footer>

        <button
            x-data="{ show: false }"
            x-init="window.addEventListener('scroll', () => { show = window.scrollY > 400 })"
            x-show="show"
            x-transition
            x-cloak
            @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="fixed bottom-6 right-6 z-50 w-11 h-11 rounded-full bg-green-600 hover:bg-green-700 text-white shadow-lg flex items-center justify-center transition duration-300"
            aria-label="Scroll to top">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
        </button>
    </body>
</html>
