@extends('layouts.public')

@section('title', config('app.name', 'Runwrk') . ' — Build Your Own Quote Calculator')

@section('content')
    <!-- Hero -->
    <section class="max-w-4xl mx-auto px-6 pt-16 sm:pt-24 pb-16 text-center">
        <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-gray-900 leading-[1.05]">
            Build Your Own<br class="hidden sm:block"> Quote Calculator
        </h1>
        <p class="mt-6 text-lg text-gray-500 max-w-2xl mx-auto">
            Create custom quote calculators for your business. Set your products, options, pricing and rules, then use them internally or give your customers a simple way to get a quote online.
        </p>
        <div class="mt-10 flex items-center justify-center gap-4">
            @auth
                <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center rounded-full bg-indigo-600 px-7 py-3.5 text-sm font-semibold text-white hover:bg-indigo-500 transition">
                    Go to Dashboard
                </a>
            @else
                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full bg-indigo-600 px-7 py-3.5 text-sm font-semibold text-white hover:bg-indigo-500 transition">
                    Get Started
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-gray-300 px-7 py-3.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Log in
                </a>
            @endauth
        </div>
    </section>

    <!-- Feature grid -->
    <section id="features" class="max-w-6xl mx-auto px-6 py-16 sm:py-24">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
            <div class="flex flex-col justify-center">
                <p class="text-xs font-bold tracking-widest text-indigo-600 uppercase">One system. Every quote.</p>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight">
                    Make Quoting Easier for Your Business
                </h2>
                <p class="mt-4 text-gray-500">
                    Manual calculations, spreadsheets, and disconnected pricing information can make quoting slower and harder to manage. Runwrk brings your products, pricing, and quote process into one place — helping your team work more consistently while giving you the option to let customers get quotes online.
                </p>
            </div>

            <x-feature-card bg="bg-amber-50" title="Internal Business Tool">
                <x-slot:icon>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7" />
                </x-slot:icon>
                Give your team one place to manage products, pricing, options, and quotes. Keep everyone working from the same information and make your quoting process more consistent.
            </x-feature-card>

            <x-feature-card bg="bg-violet-50" title="Customer-Facing Calculator">
                <x-slot:icon>
                    <rect x="4" y="3" width="16" height="18" rx="2" />
                    <path stroke-linecap="round" d="M8 7h8M8 11h3M13 11h3M8 15h3M13 15h3" />
                </x-slot:icon>
                Put your calculator on your website and let customers select what they need, see estimated pricing, and submit their quote request — without having to call or email your team.
            </x-feature-card>

            <x-feature-card bg="bg-teal-50" title="DIY — Build It Yourself">
                <x-slot:icon>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a4 4 0 00-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 005.4-5.4l-2.5 2.5-2-2 2.5-2.5z" />
                </x-slot:icon>
                You know your business best. Set up your products, pricing, and rules yourself, make changes whenever you need, and keep complete control of your quote system.
            </x-feature-card>

            <x-feature-card bg="bg-rose-50" title="DFY — We Build It For You">
                <x-slot:icon>
                    <circle cx="12" cy="8" r="3.25" />
                    <path stroke-linecap="round" d="M5.5 20c1.4-3.2 4-4.8 6.5-4.8s5.1 1.6 6.5 4.8" />
                </x-slot:icon>
                Prefer to hand it off? Our team sets up your quote calculator for you, so you can start using it right away without spending time learning or configuring the system yourself.
            </x-feature-card>

            <x-feature-card bg="bg-amber-50" title="Built Around Your Business">
                <x-slot:icon>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V9l5-4 5 4v12M4 21h16M9 21v-6h4v6M14 21V9l3-2 3 2v12" />
                </x-slot:icon>
                Every business quotes differently. Runwrk gives you the flexibility to create a quoting process that fits the way your business actually works — no forced-fit templates.
            </x-feature-card>
        </div>
    </section>

    <!-- Templates CTA -->
    <section class="max-w-6xl mx-auto px-6 pb-24">
        <div class="rounded-3xl bg-indigo-700 px-8 py-12 sm:px-14 sm:py-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white max-w-xl">
                Start Fast With Industry Templates
            </h2>
            <p class="mt-4 text-indigo-100 max-w-xl">
                Why start from a blank page? Runwrk comes with ready-made templates for printing, HVAC, cabinets, furniture, manufacturing, and more — each built around real pricing scenarios from that industry. Pick the closest match to your business, then customize every product, question, and price to fit exactly how you quote. It's the fastest way to go from sign-up to your first live calculator.
            </p>
            <a href="{{ route('pricing') }}" class="mt-8 inline-flex items-center justify-center rounded-full bg-rose-300 px-7 py-3.5 text-sm font-bold text-indigo-950 hover:bg-rose-200 transition">
                Start now
            </a>
        </div>
    </section>
@endsection
