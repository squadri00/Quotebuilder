<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Complete Your Subscription — {{ config('app.name', 'QuoteBuilder') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="max-w-xl mx-auto px-6 py-12">
            @include('partials.checkout-content', [
                'plan' => $plan,
                'pending' => $pending,
                'tax' => $tax,
                'cancelUrl' => 'http://localhost/quotebuilder/pricing.php',
            ])
        </div>

        @include('public._embed-resize')
    </body>
</html>
