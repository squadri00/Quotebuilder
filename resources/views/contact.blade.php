@extends('layouts.public')

@section('title', 'Contact — ' . config('app.name', 'Quotaire'))

@section('content')
    <section class="max-w-3xl mx-auto px-6 pt-16 sm:pt-20 pb-20">
        <div class="text-center" data-aos="fade-up" data-aos-once="true">
            <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-50 text-green-700 text-sm font-semibold">
                Get in touch
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold leading-tight text-gray-900">
                We'd love to <span class="text-green-600">hear from you</span>
            </h1>
            <p class="mt-6 text-lg text-gray-600 leading-relaxed">
                Questions about pricing, a feature you need, or help getting your first calculator live — send us a message and we'll get back to you.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center" data-aos="fade-up" data-aos-once="true">
            @if (optional($platformSettings ?? null)->contact_email)
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Email</p>
                    <a href="mailto:{{ $platformSettings->contact_email }}" class="mt-1 block text-sm font-semibold text-gray-900 hover:text-green-600">{{ $platformSettings->contact_email }}</a>
                </div>
            @endif
            @if (optional($platformSettings ?? null)->phone)
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Phone</p>
                    <a href="tel:{{ $platformSettings->phone }}" class="mt-1 block text-sm font-semibold text-gray-900 hover:text-green-600">{{ $platformSettings->phone }}</a>
                </div>
            @endif
            @if (optional($platformSettings ?? null)->legal_business_name)
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Company</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $platformSettings->legal_business_name }}</p>
                </div>
            @endif
        </div>

        <div class="mt-12 bg-white rounded-2xl border border-gray-200 shadow-sm p-8" data-aos="fade-up" data-aos-once="true">
            @if (session('contactSuccess'))
                <div class="mb-6 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm font-medium text-green-700">
                    Thanks — your message has been sent. We'll get back to you soon.
                </div>
            @endif

            @if ($errors->has('turnstile'))
                <p class="mb-6 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $errors->first('turnstile') }}</p>
            @endif

            <form method="POST" action="{{ route('contact.submit') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            class="block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm py-2.5">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            class="block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm py-2.5">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5">Message</label>
                    <textarea id="message" name="message" rows="5" required
                        class="block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm py-2.5">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Honeypot — see ContactController::submit() --}}
                <div style="position:absolute; left:-9999px;" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                @if (($platformSettings ?? null)?->turnstile_site_key)
                    <div class="cf-turnstile" data-sitekey="{{ $platformSettings->turnstile_site_key }}"></div>
                @endif

                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 bg-green-600 text-white font-semibold rounded-full shadow-lg hover:bg-green-700 transition duration-300">
                    Send message
                </button>
            </form>
        </div>
    </section>
@endsection
