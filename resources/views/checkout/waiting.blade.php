<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="3">

        <title>Finishing Setup — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="min-h-screen flex items-center justify-center px-4">
            <div class="max-w-sm w-full bg-white border border-gray-200 rounded-xl shadow-sm p-8 text-center">
                <p class="font-semibold text-gray-900">Payment received — finishing your account setup</p>
                <p class="mt-2 text-sm text-gray-500">This usually only takes a few seconds. This page will refresh automatically.</p>
                <a href="{{ route('checkout.success', $token) }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Refresh now</a>
            </div>
        </div>
    </body>
</html>
