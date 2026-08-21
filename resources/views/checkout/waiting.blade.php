@extends('layouts.public')

@section('title', 'Finishing Setup — ' . config('app.name', 'Quotaire'))

@section('content')
    <div class="max-w-sm mx-auto px-6 py-24">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-2xl p-8 text-center">
            <p class="font-semibold text-gray-900 dark:text-gray-100">Payment received — finishing your account setup</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This usually only takes a few seconds. This page will refresh automatically.</p>
            <a href="{{ route('checkout.success', $token) }}" class="mt-4 inline-block text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300">Refresh now</a>
        </div>
    </div>

    <script>
        setTimeout(function () {
            window.location.reload();
        }, 3000);
    </script>
@endsection
