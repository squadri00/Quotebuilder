<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Sign Up — {{ config('app.name', 'QuoteBuilder') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        {{--
            No plan selected: free signup, so the resulting redirect to
            /dashboard opens in a new tab and this embed just sits there
            unchanged — see partials/register-form.blade.php.

            Plan selected: deliberately submitted same-frame (no target)
            instead, so the redirect Laravel sends to checkout.show lands
            *inside this iframe* — which is exactly what
            checkout/_iframe-handoff.blade.php is watching for, so it can
            hand the token up to the parent page (e.g. checkout.php) to do
            a real top-level navigation there.

            Card styling here deliberately matches layouts/guest.blade.php's
            real card (max-w-md, white, bordered) — without that layout's
            own min-h-screen wrapper or logo header, which don't belong
            inside someone else's page.
        --}}
        <div class="flex justify-center px-4 py-12">
            <div class="w-full max-w-md px-8 py-10 bg-white border border-gray-200 shadow-sm rounded-xl">
                @include('partials.register-form', [
                    'selectedPlan' => $selectedPlan,
                    'formTarget' => $selectedPlan ? '' : 'target="_blank"',
                    'linkTarget' => 'target="_blank"',
                    'topTarget' => 'target="_top"',
                    'pricingUrl' => 'http://localhost/quotebuilder/pricing.php',
                    'loginUrl' => 'http://localhost/quotebuilder/login.php',
                ])
            </div>
        </div>

        @include('public._embed-resize')
    </body>
</html>
