@extends('layouts.public')

@section('title', 'Log In — ' . config('app.name', 'Quotaire'))

@section('content')
    <div class="max-w-md mx-auto px-6 py-16">
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl px-6 py-8">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            @include('partials.login-form')
        </div>
    </div>
@endsection
