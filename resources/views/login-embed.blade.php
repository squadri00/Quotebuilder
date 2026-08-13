<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Log In — {{ config('app.name', 'QuoteBuilder') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        {{--
            Successful login always opens the dashboard in a new tab — same
            "pop out and forget" reasoning as register-embed's free-signup
            path: the dashboard is a fully separate authenticated app
            experience that shouldn't render inside someone else's
            marketing-site iframe.
        --}}
        <div class="flex justify-center px-4 py-12">
            <div class="w-full max-w-md px-8 py-10 bg-white border border-gray-200 shadow-sm rounded-xl">
                <x-auth-session-status class="mb-4" :status="session('status')" />

                @include('partials.login-form', [
                    'formTarget' => 'target="_blank"',
                    'forgotPasswordUrl' => 'http://localhost/quotebuilder/forgot-password.php',
                    'forgotPasswordTarget' => 'target="_top"',
                ])
            </div>
        </div>

        @include('public._embed-resize')
    </body>
</html>
