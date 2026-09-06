<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Super Admin — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-950 text-white">
        @php
            $platformSettings = \App\Models\PlatformSetting::get();
            $logoFull = $platformSettings->logo_path && $platformSettings->logo_display_style === 'full';
        @endphp
        <div class="min-h-screen flex flex-col items-center justify-center px-4">
            <div class="flex items-center gap-2 mb-8">
                @if ($platformSettings->dark_logo_path || $platformSettings->logo_path)
                    {{-- Fixed dark background here (no theme toggle on this
                         screen), so this always wants the dark-mode logo
                         variant specifically, same reasoning as the
                         affiliate portal's dark sidebar. --}}
                    <img src="{{ Storage::url($platformSettings->dark_logo_path ?: $platformSettings->logo_path) }}" alt="{{ $platformSettings->platform_name ?: config('app.name') }}" class="{{ $logoFull ? 'h-10 w-auto max-w-[220px] object-contain' : 'h-10 w-10 shrink-0 rounded-lg object-contain' }}">
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-lg font-bold text-white">{{ Str::upper(Str::substr(config('app.name'), 0, 1)) }}</span>
                @endif
                <div>
                    @unless ($logoFull)
                        <p class="font-bold text-white leading-tight">{{ $platformSettings->platform_name ?: config('app.name') }}</p>
                    @endunless
                    <p class="text-xs text-gray-400 dark:text-gray-500 leading-tight uppercase tracking-wide">Super Admin</p>
                </div>
            </div>

            <div class="w-full sm:max-w-sm px-6 py-8 bg-gray-900 border border-gray-800 rounded-xl shadow-sm">
                @if (session('status'))
                    <div class="mb-4 text-sm font-medium text-green-400">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('superadmin.login.store') }}">
                    @csrf

                    <div>
                        <label for="email" class="block font-medium text-sm text-gray-300 mb-1.5">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            class="block w-full rounded-lg border-gray-700 bg-gray-800 text-white shadow-sm text-sm py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('email')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <label for="password" class="block font-medium text-sm text-gray-300 mb-1.5">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="block w-full rounded-lg border-gray-700 bg-gray-800 text-white shadow-sm text-sm py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <button type="submit" class="mt-6 w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 rounded-lg text-sm font-semibold text-white hover:bg-indigo-500 transition">
                        Log in
                    </button>
                </form>
            </div>

            <a href="{{ url('/') }}" class="mt-8 text-sm font-medium text-gray-400 hover:text-white transition">&larr; Back to website</a>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">This is a platform-level login, separate from business accounts.</p>
        </div>
    </body>
</html>
