@props(['title' => 'Partner Portal', 'active' => '', 'auth' => true])

@php
    $brand = \App\Models\PlatformSetting::get()->platform_name ?: config('app.name');
    $nav = [
        'dashboard'   => ['affiliate.portal.dashboard',   'Dashboard'],
        'referrals'   => ['affiliate.portal.referrals',   'My referrals'],
        'prospects'   => ['affiliate.portal.prospects',   'Prospects & market'],
        'commissions' => ['affiliate.portal.commissions', 'Commissions'],
        'statements'  => ['affiliate.portal.statements',  'Statements'],
        'estimator'   => ['affiliate.portal.estimator',   'Estimator'],
        'profile'     => ['affiliate.portal.profile',     'Profile'],
    ];
    $partner = auth('affiliate')->user();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} — {{ $brand }} Partners</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest-affiliate.json') }}">
    <meta name="theme-color" content="#ea580c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $brand }} Partners">
    <link rel="apple-touch-icon" href="{{ asset('icons/affiliate-192.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">

@if (! $auth)
    <div class="mx-auto mt-16 w-full max-w-md px-4">
        <p class="mb-4 text-center text-xl font-bold">{{ $brand }} <span class="text-orange-600">Partners</span></p>
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>
        @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            {{ $slot }}
        </div>
    </div>
@else
    <div class="flex min-h-screen">
        <div id="mobile-nav-backdrop" class="fixed inset-0 z-30 hidden bg-black/50 md:hidden" onclick="toggleMobileNav()"></div>

        <aside id="mobile-nav-sidebar" class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full overflow-y-auto bg-slate-900 py-5 text-slate-300 transition-transform duration-200 md:static md:z-auto md:w-60 md:shrink-0 md:translate-x-0">
            <div class="flex items-center justify-between px-5 pb-4">
                <span class="text-lg font-bold text-white">{{ $brand }} <span class="text-orange-500">Partners</span></span>
                <button type="button" onclick="toggleMobileNav()" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/5 hover:text-white md:hidden" aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="space-y-0.5">
                @foreach ($nav as $key => [$route, $label])
                    <a href="{{ route($route) }}"
                       class="block px-5 py-2.5 text-sm hover:bg-white/5 hover:text-white {{ $active === $key ? 'bg-white/10 text-white shadow-[inset_3px_0_0_#ea580c]' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
                <form method="POST" action="{{ route('affiliate.portal.logout') }}">
                    @csrf
                    <button class="block w-full px-5 py-2.5 text-left text-sm hover:bg-white/5 hover:text-white">Sign out</button>
                </form>
            </nav>
        </aside>

        <main class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-3.5">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" onclick="toggleMobileNav()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Open menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>
                    <h1 class="truncate text-lg font-semibold">{{ $title }}</h1>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <button id="pwa-install-btn" onclick="pwaInstall()" class="flex items-center gap-1.5 rounded-lg bg-orange-600 px-2.5 py-1.5 text-sm font-semibold text-white hover:bg-orange-700 sm:px-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span class="hidden sm:inline">Install app</span>
                    </button>
                    <div class="hidden text-sm text-slate-500 sm:block">
                        {{ $partner?->name }} ·
                        <span class="rounded bg-orange-100 px-2 py-0.5 font-mono text-xs font-semibold text-orange-700">{{ $partner?->partner_code }}</span>
                    </div>
                </div>
            </div>
            <div class="mx-auto max-w-5xl px-6 py-6">
                @if (session('affiliate_impersonated_by'))
                    <div class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Viewing this portal as the partner (opened from Super Admin).
                        <form method="POST" action="{{ route('affiliate.portal.logout') }}" class="inline">@csrf
                            <button class="underline">Exit</button>
                        </form>
                    </div>
                @endif
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
@endif

<script>
    function toggleMobileNav() {
        document.getElementById('mobile-nav-sidebar').classList.toggle('-translate-x-full');
        document.getElementById('mobile-nav-backdrop').classList.toggle('hidden');
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '{{ url('/affiliate') }}' }).catch(function () {});
    }

    var pwaDeferredPrompt = null;
    var pwaBtn = document.getElementById('pwa-install-btn');
    var pwaStorageKey = 'quotaire-affiliate-installed';
    if (pwaBtn && (window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone
        || localStorage.getItem(pwaStorageKey) === '1')) {
        pwaBtn.classList.add('hidden');
    }
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        pwaDeferredPrompt = e;
    });
    window.addEventListener('appinstalled', function () {
        pwaDeferredPrompt = null;
        if (pwaBtn) pwaBtn.classList.add('hidden');
        try { localStorage.setItem(pwaStorageKey, '1'); } catch (e) {}
    });
    function pwaInstall() {
        if (pwaDeferredPrompt) {
            pwaDeferredPrompt.prompt();
            pwaDeferredPrompt.userChoice.finally(function () { pwaDeferredPrompt = null; });
            return;
        }
        var ua = navigator.userAgent;
        var msg = /iphone|ipad|ipod/i.test(ua)
            ? 'To install: tap the Share icon in Safari, then "Add to Home Screen".'
            : /android/i.test(ua)
                ? 'To install: open Chrome\'s ⋮ menu (top right) and tap "Add to Home screen" or "Install app".'
                : 'To install the Partner Portal as a desktop app:\n\n1. Click the ⋮ menu (top right)\n2. Go to "Cast, save, and share"\n3. Click "Install page as app…" (not "Create shortcut")\n4. Confirm in the dialog that appears\n\nThis installs it as a real app — its own window, its own icon, listed in chrome://apps.';
        alert(msg);
    }
</script>
</body>
</html>
