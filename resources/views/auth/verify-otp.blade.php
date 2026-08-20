@extends('layouts.public')

@section('title', 'Verify your email — ' . config('app.name', 'Quotaire'))

@section('content')
    <div class="max-w-md mx-auto px-6 py-16">
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl px-6 py-8">
            <h1 class="text-xl font-semibold text-gray-900">Verify your email</h1>
            <p class="mt-2 text-sm text-gray-600">
                We sent a 6-digit code to <span class="font-medium text-gray-900">{{ $pending->email }}</span>.
                Enter it below to finish setting up your free account.
            </p>

            @if (session('status'))
                <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.verify', $pending->token) }}" class="mt-6">
                @csrf

                <x-input-label for="code" :value="__('Verification code')" />
                <x-text-input id="code" class="block mt-1 w-full text-center text-2xl tracking-[0.5em]" type="text"
                    name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />

                <x-primary-button class="w-full justify-center mt-4">
                    {{ __('Verify & Create Account') }}
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('register.verify.resend', $pending->token) }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-500 underline">
                    Didn't get a code? Resend
                </button>
            </form>
        </div>
    </div>
@endsection
