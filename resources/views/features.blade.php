@extends('layouts.public')

@section('title', 'Features — ' . config('app.name', 'Quotaire'))

@section('content')
    <section class="py-16 lg:py-20 bg-white dark:bg-gray-900">
        <div class="max-w-6xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-14">

                <div class="w-full lg:w-1/2" data-aos="fade-right" data-aos-once="true">
                    @if ($hero->eyebrow_text)
                        <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-sm font-semibold">
                            {{ $hero->eyebrow_text }}
                        </div>
                    @endif

                    <h1 class="text-4xl sm:text-5xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                        {{ $hero->heading }}
                    </h1>

                    <p class="mt-6 text-lg text-gray-600 dark:text-gray-400 leading-relaxed max-w-xl">
                        {{ $hero->subheading }}
                    </p>
                </div>

                <div class="w-full lg:w-1/2" data-aos="fade-left" data-aos-once="true">
                    <div class="relative bg-gray-900 rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center space-x-2 mb-4">
                            <span class="w-3 h-3 rounded-full bg-red-400"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                            <span class="w-3 h-3 rounded-full bg-green-400"></span>
                            <span class="ml-3 text-xs text-gray-400">app.{{ Str::lower(config('app.name', 'quotaire')) }}.com</span>
                        </div>
                        <div class="bg-white rounded-xl p-5">
                            <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">Set rules</p>
                            <div class="space-y-3 text-sm">
                                <div class="border border-gray-200 rounded-lg px-4 py-3 flex justify-between">
                                    <span class="text-gray-700">Rush turnaround</span><span class="font-semibold text-gray-900">+15%</span>
                                </div>
                                <div class="border border-gray-200 rounded-lg px-4 py-3 flex justify-between">
                                    <span class="text-gray-700">Qty over 500</span><span class="font-semibold text-gray-900">+$50 fixed</span>
                                </div>
                                <div class="border border-gray-200 rounded-lg px-4 py-3 flex justify-between">
                                    <span class="text-gray-700">Kiln dried</span><span class="font-semibold text-gray-900">+10%</span>
                                </div>
                                <button class="w-full py-3 bg-green-600 text-white font-semibold rounded-lg">+ Add rule</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="bg-gray-50 dark:bg-gray-800/50 py-16 lg:py-24">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 text-center">

            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100">Powerful features, zero complexity</h2>
            <p class="mt-4 text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">Every tool you need to build, price, and send quotes — designed for business owners, not developers. Explore what {{ config('app.name', 'Quotaire') }} can do for you.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-14 text-left">

                @foreach ($features as $feature)
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 p-7" data-aos="fade-up" data-aos-once="true">
                        <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 mb-5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature->icon }}"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">{{ $feature->title }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ $feature->body }}</p>
                    </div>
                @endforeach

            </div>

            <div class="mt-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 p-7 text-left max-w-2xl mx-auto md:max-w-none md:flex md:items-start md:gap-6" data-aos="fade-up" data-aos-once="true">
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 mb-5 md:mb-0 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m8-14h.01"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">Industry templates</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed max-w-2xl">Get started fast with pre-built templates for printing, HVAC, cabinets, manufacturing, remodeling, and furniture — fully editable to match your exact pricing. <a href="{{ route('demo') }}" class="text-green-600 dark:text-green-400 font-semibold hover:underline">Try a live one &rarr;</a></p>
                </div>
            </div>

        </div>
    </section>

    <section class="bg-white dark:bg-gray-900 py-16 lg:py-24">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100">See every feature in action</h2>
            <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center mt-8 px-8 py-4 bg-green-600 text-white font-semibold rounded-full shadow-lg hover:bg-green-700 transition duration-300">
                Start free today
            </a>
        </div>
    </section>
@endsection
