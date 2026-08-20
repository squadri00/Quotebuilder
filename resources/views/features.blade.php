@extends('layouts.public')

@section('title', 'Features — ' . config('app.name', 'Quotaire'))

@section('content')
    <section class="py-16 lg:py-20 bg-white dark:bg-gray-900">
        <div class="max-w-6xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-14">

                <div class="w-full lg:w-1/2" data-aos="fade-right" data-aos-once="true">
                    <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-sm font-semibold">
                        Everything you need to quote faster
                    </div>

                    <h1 class="text-4xl sm:text-5xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                        From no-code pricing rules to instant PDF quotes, {{ config('app.name', 'Quotaire') }} gives you every tool to turn browsers into buyers.
                    </h1>

                    <p class="mt-6 text-lg text-gray-600 dark:text-gray-400 leading-relaxed max-w-xl">
                        Build custom calculators, automate your pricing logic, and deliver instant quotes your customers can trust — all without writing a single line of code. See how each feature works together to save you time and win more business.
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

                @php
                    $features = [
                        ['icon' => 'M9 3v2m6-2v2M4 7h16M5 7h14v13a1 1 0 01-1 1H6a1 1 0 01-1-1V7z', 'title' => 'No-code quote builder', 'body' => 'Build fully custom pricing calculators without writing a single line of code. Add products, questions, and options through simple point-and-click forms — no developer needed, ever.'],
                        ['icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'title' => 'Smart pricing rules engine', 'body' => 'Set up fixed fees, percentage surcharges, price overrides, and quantity-based pricing with simple dropdown logic. Your pricing can be as simple or as detailed as your business needs.'],
                        ['icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'title' => 'Question paths (conditional logic)', 'body' => 'Show customers only the questions that apply to them. Ask about delivery details only if they choose delivery, or show finish options only for certain products — keeping the quote experience fast and relevant.'],
                        ['icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9 10h6m-6 4h4', 'title' => 'Embed anywhere', 'body' => 'Paste one line of code and your quote calculator works on any website — WordPress, Wix, Squarespace, or custom-built. Fully responsive on desktop, tablet, and mobile.'],
                        ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'title' => 'Instant branded PDF quotes', 'body' => 'Every completed quote generates a professional, itemized PDF automatically — with your logo, pricing breakdown, and terms — ready to email or download in seconds.'],
                        ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'title' => 'Quotes inbox & status tracking', 'body' => 'Every quote request lands in one organized inbox. Track status from new to sent to accepted, so nothing falls through the cracks.'],
                    ];
                @endphp

                @foreach ($features as $feature)
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 p-7" data-aos="fade-up" data-aos-once="true">
                        <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 mb-5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">{{ $feature['title'] }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ $feature['body'] }}</p>
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
