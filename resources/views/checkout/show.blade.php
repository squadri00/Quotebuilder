@extends('layouts.public')

@section('title', 'Complete Your Subscription — ' . config('app.name', 'Quotaire'))

@section('content')
    <div class="max-w-xl mx-auto px-6 py-16">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-2xl px-6 py-8">
            @include('partials.checkout-content', ['plan' => $plan, 'pending' => $pending, 'tax' => $tax])
        </div>
    </div>
@endsection
