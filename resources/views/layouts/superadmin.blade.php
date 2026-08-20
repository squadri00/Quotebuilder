<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ Auth::guard('admin')->check() && Auth::guard('admin')->user()->theme === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Super Admin' }} — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: true }" class="flex h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Dark sidebar — deliberately distinct from the light business-admin sidebar -->
            <aside
                :class="sidebarOpen ? 'w-64' : 'w-20'"
                class="flex shrink-0 flex-col border-r border-gray-800 bg-gray-900 transition-all duration-200"
            >
                @php $logoFull = ($platformSettings ?? null)?->logo_path && $platformSettings->logo_display_style === 'full'; @endphp
                <div class="flex h-16 shrink-0 items-center {{ $logoFull ? 'justify-center' : '' }} border-b border-gray-800 px-3">
                    <a href="{{ route('superadmin.dashboard') }}" class="flex items-center overflow-hidden {{ $logoFull ? 'w-full justify-center' : '' }}">
                        <x-platform-logo :settings="$platformSettings ?? null"
                            :img-class="$logoFull ? 'max-h-14 w-full object-contain' : 'h-10 w-10 shrink-0 rounded-lg object-contain'" />
                        @unless ($logoFull)
                            <span x-show="sidebarOpen" x-cloak class="ml-2 whitespace-nowrap font-bold text-white">{{ config('app.name') }}</span>
                        @endunless
                    </a>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    <x-superadmin-nav-link :href="route('superadmin.dashboard')" :active="request()->routeIs('superadmin.dashboard')">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                        </x-slot>
                        {{ __('Dashboard') }}
                    </x-superadmin-nav-link>

                    <x-superadmin-nav-group title="Tenants" :active="request()->routeIs(['superadmin.businesses.*', 'superadmin.users.*', 'superadmin.industries.*'])">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </x-slot>

                        @if (Route::has('superadmin.businesses.index'))
                            <x-superadmin-nav-link :href="route('superadmin.businesses.index')" :active="request()->routeIs('superadmin.businesses.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                </x-slot>
                                {{ __('Businesses') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.users.index'))
                            <x-superadmin-nav-link :href="route('superadmin.users.index')" :active="request()->routeIs('superadmin.users.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </x-slot>
                                {{ __('Users') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.industries.index'))
                            <x-superadmin-nav-link :href="route('superadmin.industries.index')" :active="request()->routeIs('superadmin.industries.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m9-13.5h4.5m-4.5 3h4.5m-4.5 3h4.5M9 6.75h.008v.008H9V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM9 10.5h.008v.008H9V10.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM9 14.25h.008v.008H9v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                </x-slot>
                                {{ __('Industries') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.countries.index'))
                            <x-superadmin-nav-link :href="route('superadmin.countries.index')" :active="request()->routeIs('superadmin.countries.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                    </svg>
                                </x-slot>
                                {{ __('Countries') }}
                            </x-superadmin-nav-link>
                        @endif
                    </x-superadmin-nav-group>

                    <x-superadmin-nav-group title="Catalog" :active="request()->routeIs(['superadmin.templates.*', 'superadmin.quotes.*', 'superadmin.training.*'])">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15.5A2.25 2.25 0 0022.25 18v-4.162c0-.224-.034-.447-.1-.661L20.238 5.338a2.25 2.25 0 00-2.15-1.588H15M9 3.75v11.25m0-11.25L11.25 9M9 3.75L6.75 9M15 3.75v11.25m0-11.25L12.75 9M15 3.75l2.25 5.25" />
                            </svg>
                        </x-slot>

                        @if (Route::has('superadmin.templates.index'))
                            <x-superadmin-nav-link :href="route('superadmin.templates.index')" :active="request()->routeIs('superadmin.templates.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15.5A2.25 2.25 0 0022.25 18v-4.162c0-.224-.034-.447-.1-.661L20.238 5.338a2.25 2.25 0 00-2.15-1.588H15M9 3.75v11.25m0-11.25L11.25 9M9 3.75L6.75 9M15 3.75v11.25m0-11.25L12.75 9M15 3.75l2.25 5.25" />
                                    </svg>
                                </x-slot>
                                {{ __('Templates') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.quotes.create'))
                            <x-superadmin-nav-link :href="route('superadmin.quotes.create')" :active="request()->routeIs('superadmin.quotes.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.611L5 14.5" />
                                    </svg>
                                </x-slot>
                                {{ __('Demo Quotes') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.training.index'))
                            <x-superadmin-nav-link :href="route('superadmin.training.index')" :active="request()->routeIs('superadmin.training.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                    </svg>
                                </x-slot>
                                {{ __('Training') }}
                            </x-superadmin-nav-link>
                        @endif
                    </x-superadmin-nav-group>

                    <x-superadmin-nav-group title="Billing" :active="request()->routeIs(['superadmin.plans.*', 'superadmin.features.*', 'superadmin.platform-tax-rates.*', 'superadmin.implementation-tiers.*', 'superadmin.implementation-orders.*'])">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </x-slot>

                        @if (Route::has('superadmin.plans.index'))
                            <x-superadmin-nav-link :href="route('superadmin.plans.index')" :active="request()->routeIs('superadmin.plans.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25l9.75-6 9.75 6M2.25 8.25v10.5A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V8.25M2.25 8.25L12 14.25l9.75-6M9 21v-6a2.25 2.25 0 012.25-2.25h1.5A2.25 2.25 0 0115 15v6" />
                                    </svg>
                                </x-slot>
                                {{ __('Plans') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.features.index'))
                            <x-superadmin-nav-link :href="route('superadmin.features.index')" :active="request()->routeIs('superadmin.features.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                                    </svg>
                                </x-slot>
                                {{ __('Features') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.platform-tax-rates.index'))
                            <x-superadmin-nav-link :href="route('superadmin.platform-tax-rates.index')" :active="request()->routeIs('superadmin.platform-tax-rates.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m-5.25-.75h.008v.008H9.75V7.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM14.25 13.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </x-slot>
                                {{ __('Platform Tax Rates') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.implementation-tiers.index'))
                            <x-superadmin-nav-link :href="route('superadmin.implementation-tiers.index')" :active="request()->routeIs('superadmin.implementation-tiers.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-2.268-9.64a3.375 3.375 0 00-3.285-2.602H7.923a3.375 3.375 0 00-3.285 2.602l-2.268 9.64a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3m16.5 0h.008v.008h-.008v-.008zm-3 0h.008v.008h-.008v-.008z" />
                                    </svg>
                                </x-slot>
                                {{ __('Implementation Tiers') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.implementation-orders.index'))
                            <x-superadmin-nav-link :href="route('superadmin.implementation-orders.index')" :active="request()->routeIs('superadmin.implementation-orders.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                </x-slot>
                                {{ __('Implementation Orders') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.financial.index'))
                            <x-superadmin-nav-link :href="route('superadmin.financial.index')" :active="request()->routeIs('superadmin.financial.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />
                                    </svg>
                                </x-slot>
                                {{ __('Financial Activity') }}
                            </x-superadmin-nav-link>
                        @endif
                    </x-superadmin-nav-group>

                    <x-superadmin-nav-group title="Support" :active="request()->routeIs(['superadmin.support.*', 'superadmin.support-addon.*', 'superadmin.announcements.*'])">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25S3 16.556 3 12 7.03 3.75 12 3.75 21 7.444 21 12z" />
                            </svg>
                        </x-slot>

                        @if (Route::has('superadmin.support.index'))
                            <x-superadmin-nav-link :href="route('superadmin.support.index')" :active="request()->routeIs('superadmin.support.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25S3 16.556 3 12 7.03 3.75 12 3.75 21 7.444 21 12z" />
                                    </svg>
                                </x-slot>
                                {{ __('Support Tickets') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.support-addon.edit'))
                            <x-superadmin-nav-link :href="route('superadmin.support-addon.edit')" :active="request()->routeIs('superadmin.support-addon.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                    </svg>
                                </x-slot>
                                {{ __('Support Add-on') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.announcements.index'))
                            <x-superadmin-nav-link :href="route('superadmin.announcements.index')" :active="request()->routeIs('superadmin.announcements.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
                                    </svg>
                                </x-slot>
                                {{ __('Announcements') }}
                            </x-superadmin-nav-link>
                        @endif
                    </x-superadmin-nav-group>

                    <x-superadmin-nav-group title="System" :active="request()->routeIs(['superadmin.audit-log.*', 'superadmin.settings.*', 'superadmin.password.*'])">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.559-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.164-.398.142-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </x-slot>

                        @if (Route::has('superadmin.audit-log.index'))
                            <x-superadmin-nav-link :href="route('superadmin.audit-log.index')" :active="request()->routeIs('superadmin.audit-log.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </x-slot>
                                {{ __('Audit Log') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.settings.edit'))
                            <x-superadmin-nav-link :href="route('superadmin.settings.edit')" :active="request()->routeIs('superadmin.settings.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.559-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.164-.398.142-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </x-slot>
                                {{ __('Platform Settings') }}
                            </x-superadmin-nav-link>
                        @endif

                        @if (Route::has('superadmin.password.edit'))
                            <x-superadmin-nav-link :href="route('superadmin.password.edit')" :active="request()->routeIs('superadmin.password.*')">
                                <x-slot name="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                    </svg>
                                </x-slot>
                                {{ __('Change Password') }}
                            </x-superadmin-nav-link>
                        @endif
                    </x-superadmin-nav-group>
                </nav>

                <div class="border-t border-gray-800 px-3 py-3">
                    <div class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-700 text-xs font-semibold text-white">
                            {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1)) }}
                        </span>
                        <span x-show="sidebarOpen" x-cloak class="min-w-0 flex-1 text-left">
                            <span class="block truncate font-medium text-white">{{ Auth::guard('admin')->user()->name }}</span>
                            <span class="block truncate text-xs text-gray-400">{{ Auth::guard('admin')->user()->email }}{{ ($platformSettings ?? null)?->version ? ' · v'.$platformSettings->version : '' }}</span>
                        </span>
                    </div>
                </div>

                <div class="border-t border-gray-800 p-3 space-y-1">
                    <form method="POST" action="{{ route('superadmin.logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition duration-150 ease-in-out">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15m-3 0l-3-3m0 0l3-3m-3 3H15" />
                            </svg>
                            <span x-show="sidebarOpen" x-cloak>{{ __('Log Out') }}</span>
                        </button>
                    </form>

                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition duration-150 ease-in-out"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            class="h-5 w-5 shrink-0 transition-transform duration-200" :class="!sidebarOpen && 'rotate-180'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                        <span x-show="sidebarOpen" x-cloak>{{ __('Collapse') }}</span>
                    </button>
                </div>
            </aside>

            <div class="flex flex-1 flex-col overflow-hidden">
                <header class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="min-w-0 flex-1">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <a
                            href="{{ url('/') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            title="Visit {{ config('app.name', 'Quotaire') }}.com"
                            aria-label="Visit {{ config('app.name', 'Quotaire') }}.com (opens in a new tab)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>

                    <button
                        type="button"
                        onclick="toggleSuperAdminTheme()"
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
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto bg-gray-100 p-6 dark:bg-gray-900 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            function toggleSuperAdminTheme() {
                var isDark = document.documentElement.classList.toggle('dark');

                fetch('{{ route('superadmin.theme.update') }}', {
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
