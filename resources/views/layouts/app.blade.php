<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->check() && auth()->user()->theme === 'dark' ? 'dark' : '' }}" style="--brand-color: {{ auth()->user()->business->brand_color ?? '#4f46e5' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: true }" class="flex h-screen bg-gray-50 dark:bg-gray-900">
            @include('layouts.sidebar-navigation')

            <div class="flex flex-1 flex-col overflow-hidden">
                @if (session('impersonating_business_id'))
                    <div class="flex shrink-0 items-center justify-between gap-4 bg-amber-500 px-6 py-2 text-sm font-medium text-white">
                        <span>You are impersonating {{ session('impersonating_business_name') }} as a super admin.</span>
                        <form method="POST" action="{{ route('superadmin.impersonate.exit') }}">
                            @csrf
                            <button type="submit" class="underline hover:no-underline">Exit impersonation</button>
                        </form>
                    </div>
                @endif

                <!-- Page header -->
                <header class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="min-w-0 flex-1">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            type="button"
                            onclick="toggleAppTheme()"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            title="Toggle dark mode"
                            aria-label="Toggle dark mode"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 dark:hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-5 w-5 dark:block">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                        </button>

                        <x-dropdown width="w-80" align="right">
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                    title="Announcements"
                                    aria-label="Announcements"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    @if ($headerAnnouncementCount > 0)
                                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                                            {{ $headerAnnouncementCount > 9 ? '9+' : $headerAnnouncementCount }}
                                        </span>
                                    @endif
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Announcements</span>
                                </div>

                                @forelse ($headerAnnouncements as $announcement)
                                    @php
                                        $dotColor = match ($announcement->severity) {
                                            'critical' => 'bg-red-500',
                                            'warning' => 'bg-amber-500',
                                            default => 'bg-blue-500',
                                        };
                                    @endphp
                                    <div class="px-4 py-3 border-b border-gray-100 last:border-b-0 dark:border-gray-700">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dotColor }}"></span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $announcement->title }}</p>
                                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ $announcement->message }}</p>
                                                <form method="POST" action="{{ route('announcements.dismiss', $announcement) }}" class="mt-1">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Dismiss</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No new announcements.</p>
                                @endforelse

                                <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700">
                                    <a href="{{ route('announcements.index') }}" class="text-xs font-medium brand-text">View all announcements</a>
                                </div>
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown width="w-56">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50 transition duration-150 ease-in-out dark:hover:bg-gray-700">
                                    @if (Auth::user()->business?->logo_path)
                                        <img src="{{ Storage::url(Auth::user()->business->logo_path) }}" alt="{{ Auth::user()->business->name }}" class="h-8 w-8 shrink-0 rounded-full object-cover border border-gray-200 dark:border-gray-600">
                                    @else
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <span class="hidden text-left sm:block">
                                        <span class="block truncate font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</span>
                                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-gray-400">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <!-- Page content -->
                <main class="flex-1 overflow-y-auto p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            function toggleAppTheme() {
                var isDark = document.documentElement.classList.toggle('dark');

                fetch('{{ route('theme.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ theme: isDark ? 'dark' : 'light' }),
                });
            }
        </script>
    </body>
</html>
