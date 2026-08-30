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
        <aside class="w-60 shrink-0 bg-slate-900 py-5 text-slate-300">
            <div class="px-5 pb-4 text-lg font-bold text-white">{{ $brand }} <span class="text-orange-500">Partners</span></div>
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
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3.5">
                <h1 class="text-lg font-semibold">{{ $title }}</h1>
                <div class="text-sm text-slate-500">
                    {{ $partner?->name }} ·
                    <span class="rounded bg-orange-100 px-2 py-0.5 font-mono text-xs font-semibold text-orange-700">{{ $partner?->partner_code }}</span>
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
</body>
</html>
