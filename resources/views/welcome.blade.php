@extends('layouts.public')

@section('title', config('app.name', 'Quotaire') . ' — Build Your Own Quote Calculator')

@section('content')
    <!-- Hero -->
    <div class="bg-cream dark:bg-green-950">
        <div class="max-w-6xl px-6 mx-auto flex flex-col lg:flex-row items-stretch">
            <div class="flex flex-col w-full lg:w-5/12 justify-center lg:pt-6 items-start text-center lg:text-left mb-5 md:mb-0">
                <h1 data-aos="fade-right" data-aos-once="true" class="my-4 text-5xl sm:text-6xl font-bold leading-tight text-navy dark:text-gray-100">
                    {!! $hero->headingHtml() !!}
                </h1>
                <p data-aos="fade-down" data-aos-once="true" data-aos-delay="300" class="leading-normal text-xl mb-8 text-gray-600 dark:text-gray-400">{{ $hero->subheading }}</p>
                <div data-aos="fade-up" data-aos-once="true" data-aos-delay="700" class="w-full md:flex items-center justify-center lg:justify-start md:space-x-5">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="lg:mx-0 inline-block bg-green-600 text-white text-xl font-bold rounded-full py-4 px-9 hover:bg-green-700 transform transition hover:scale-105 duration-300 ease-in-out">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('pricing') }}" class="lg:mx-0 inline-block bg-green-600 text-white text-xl font-bold rounded-full py-4 px-9 hover:bg-green-700 transform transition hover:scale-105 duration-300 ease-in-out">
                            Join for free
                        </a>
                    @endauth
                </div>
            </div>
            <div class="w-full lg:w-7/12 relative">
                <img data-aos="fade-up" data-aos-once="true" class="w-full h-full object-cover mx-auto" src="{{ asset('images/quotaire/h101.png') }}" alt="">
            </div>
        </div>
        <div class="text-white dark:text-gray-900 -mt-14 sm:-mt-24 lg:-mt-36 relative">
            <svg class="xl:h-40 xl:w-full" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M600,112.77C268.63,112.77,0,65.52,0,7.23V120H1200V7.23C1200,65.52,931.37,112.77,600,112.77Z" fill="currentColor"></path>
            </svg>
            <div class="bg-white dark:bg-gray-900 w-full h-20 -mt-px"></div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 lg:px-8 text-gray-700 dark:text-gray-300 overflow-x-hidden">

        <!-- Intro video -->
        <section class="bg-white dark:bg-gray-900 overflow-hidden mt-4">
            <div class="flex flex-col lg:flex-row items-center py-16 lg:py-24">
                <div class="w-full lg:w-1/2 text-center lg:text-left" data-aos="fade-right" data-aos-once="true">
                    <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-sm font-semibold">
                        <span class="w-2 h-2 mr-2 rounded-full bg-green-500"></span>
                        Professional Quoting Made Simple
                    </div>
                    <h2 class="text-4xl sm:text-5xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                        Create accurate <span class="text-green-600 dark:text-green-400">quotes faster.</span>
                    </h2>
                    <p class="mt-6 text-lg leading-relaxed text-gray-600 dark:text-gray-400 max-w-xl mx-auto lg:mx-0">
                        Give your customers professional quotes in minutes. {{ config('app.name', 'Quotaire') }} helps businesses calculate pricing, build quotes, and send them with confidence.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="{{ route('demo') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-4 bg-green-600 text-white font-semibold rounded-lg shadow-lg hover:bg-green-700 transition duration-300">
                            Try a Live Demo
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="#how-it-works" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-4 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition duration-300">
                            See How It Works
                        </a>
                    </div>
                    <div class="mt-8 flex items-center justify-center lg:justify-start space-x-6 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Fast quoting
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Professional quotes
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 mt-14 lg:mt-0 lg:pl-12" data-aos="fade-left" data-aos-once="true">
                    <div class="max-w-3xl mx-auto overflow-hidden rounded-xl shadow-lg">
                        <video class="w-full aspect-video object-cover" controls poster="{{ asset('images/quotaire/thumbnail1.png') }}">
                            <source src="{{ asset('videos/quotaire/quotaire-master-intro.mp4') }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            </div>
        </section>

        <!-- Problem / solution -->
        <div data-aos="fade-up" data-aos-once="true" class="max-w-2xl mx-auto text-center mt-16">
            <h2 class="font-bold text-navy dark:text-gray-100 my-3 text-4xl sm:text-5xl">Manual quoting <span class="text-green-600 dark:text-green-400">is costing you business</span></h2>
            <p class="leading-relaxed text-gray-500 dark:text-gray-400">Slow estimates, spreadsheet errors, and hidden pricing all push potential customers toward a competitor who answers faster.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-14 md:gap-5 mt-20">
            <div data-aos="fade-up" data-aos-once="true" class="bg-white dark:bg-gray-800 shadow-xl p-6 text-center rounded-xl">
                <div class="bg-green-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto shadow-lg transform -translate-y-12">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-medium text-xl mb-3 lg:px-6 text-navy dark:text-gray-100">Slow Quotes Lose Sales</h3>
                <p class="px-4 text-gray-500 dark:text-gray-400">Buyers want instant answers. Waiting 24 to 48 hours for a manual estimate gives leads time to buy from your competitors.</p>
            </div>
            <div data-aos="fade-up" data-aos-once="true" data-aos-delay="150" class="bg-white dark:bg-gray-800 shadow-xl p-6 text-center rounded-xl">
                <div class="bg-orange-500 rounded-full w-16 h-16 flex items-center justify-center mx-auto shadow-lg transform -translate-y-12">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="font-medium text-xl mb-3 lg:px-6 text-navy dark:text-gray-100">Spreadsheets Break</h3>
                <p class="px-4 text-gray-500 dark:text-gray-400">One incorrect formula, missing row, or outdated rate sheet ruins your profit margins and leads to costly errors.</p>
            </div>
            <div data-aos="fade-up" data-aos-once="true" data-aos-delay="300" class="bg-white dark:bg-gray-800 shadow-xl p-6 text-center rounded-xl">
                <div class="bg-sky-500 rounded-full w-16 h-16 flex items-center justify-center mx-auto shadow-lg transform -translate-y-12">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/></svg>
                </div>
                <h3 class="font-medium text-xl mb-3 lg:px-6 text-navy dark:text-gray-100 lg:h-14 pt-3">Hidden Pricing Kills Conversions</h3>
                <p class="px-4 text-gray-500 dark:text-gray-400">Modern buyers skip businesses that require them to fill out long contact forms or book sales calls just to get a baseline price.</p>
            </div>
        </div>

        <!-- How it works -->
        <div id="how-it-works" class="md:flex mt-32 md:space-x-10 items-start scroll-mt-24">
            <div data-aos="fade-right" data-aos-once="true" class="md:w-7/12 relative">
                <img class="w-full relative z-10" src="{{ asset('images/quotaire/f17.png') }}" alt="">
                <div class="bg-green-300 w-32 h-32 rounded-full absolute z-0 left-4 -top-12 animate-pulse"></div>
                <div class="bg-sky-300 w-5 h-5 rounded-full absolute z-0 left-36 -top-12 animate-ping"></div>
                <div class="bg-indigo-400 w-36 h-36 rounded-full absolute z-0 right-16 -bottom-1 animate-pulse"></div>
                <div class="bg-rose-400 w-5 h-5 rounded-full absolute z-0 right-52 bottom-1 animate-ping"></div>
            </div>
            <div data-aos="fade-left" data-aos-once="true" class="md:w-5/12 mt-20 md:mt-0 text-gray-500 dark:text-gray-400">
                <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-sm font-semibold">
                    <span class="w-2 h-2 mr-2 rounded-full bg-green-500"></span>
                    See How it Works
                </div>
                <h2 class="text-3xl sm:text-4xl font-semibold text-navy dark:text-gray-100"><span class="text-green-600 dark:text-green-400">From price list to</span> live calculator in 4 simple steps</h2>

                <div class="flex items-start space-x-5 my-5">
                    <div class="flex-shrink-0 bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 flex items-center justify-center font-bold text-green-600 dark:text-green-400">1</div>
                    <p class="pt-2">Add Your Products: Enter your core products, services, materials, or billable options.</p>
                </div>
                <div class="flex items-start space-x-5 my-5">
                    <div class="flex-shrink-0 bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 flex items-center justify-center font-bold text-green-600 dark:text-green-400">2</div>
                    <p class="pt-2">Set Prices &amp; Rules: Define base rates, conditional logic, variable quantities, and percentage add-ons.</p>
                </div>
                <div class="flex items-start space-x-5 my-5">
                    <div class="flex-shrink-0 bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 flex items-center justify-center font-bold text-green-600 dark:text-green-400">3</div>
                    <p class="pt-2">Test &amp; Preview: Test your custom calculator in real-time to ensure every rule calculates perfectly.</p>
                </div>
                <div class="flex items-start space-x-5 my-5">
                    <div class="flex-shrink-0 bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 flex items-center justify-center font-bold text-green-600 dark:text-green-400">4</div>
                    <p class="pt-2">Embed Anywhere: Paste a lightweight code snippet into your website or use it internally.</p>
                </div>
            </div>
        </div>

        <!-- DIY vs DFY -->
        <section class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl py-16 lg:py-24 mt-24">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight text-gray-900 dark:text-gray-100 text-center mb-14">
                    Two ways to <span class="text-green-600 dark:text-green-400">get started.</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
                    <div data-aos="fade-up" data-aos-once="true" class="group relative flex flex-col h-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 overflow-hidden transition-all duration-300 ease-out hover:-translate-y-1.5 hover:shadow-xl hover:border-green-200 dark:hover:border-green-800">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-green-600 scale-x-0 origin-left group-hover:scale-x-100 transition-transform duration-300 ease-out"></div>
                        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 mb-6 transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div class="inline-flex items-center px-4 py-1.5 mb-4 rounded-full bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-xs font-bold tracking-wide w-fit">DIY</div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">Build it yourself</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed flex-1">You know your business best. Set up your products, pricing and rules yourself, make changes whenever you need, and keep complete control of your quote system.</p>
                        <a href="{{ route('pricing') }}" class="inline-flex items-center mt-6 font-semibold text-green-600 dark:text-green-400">
                            Start building
                            <svg class="w-4 h-4 ml-1.5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    <div data-aos="fade-up" data-aos-once="true" data-aos-delay="150" class="group relative flex flex-col h-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 overflow-hidden transition-all duration-300 ease-out hover:-translate-y-1.5 hover:shadow-xl hover:border-green-200 dark:hover:border-green-800">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-green-600 scale-x-0 origin-left group-hover:scale-x-100 transition-transform duration-300 ease-out"></div>
                        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 mb-6 transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/></svg>
                        </div>
                        <div class="inline-flex items-center px-4 py-1.5 mb-4 rounded-full bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-xs font-bold tracking-wide w-fit">DFY</div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">We build it for you</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed flex-1">Tell us what you sell and how you price it. Our team will build and configure your quote calculator for you, so you can start using it without spending time learning or setting up the system.</p>
                        <a href="{{ route('contact') }}" class="inline-flex items-center mt-6 font-semibold text-green-600 dark:text-green-400">
                            Get a setup quote
                            <svg class="w-4 h-4 ml-1.5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer-facing calculator -->
        <div class="flex flex-col md:flex-row items-center md:space-x-10 mt-20">
            <div data-aos="fade-right" data-aos-once="true" class="md:w-1/2">
                <h2 class="text-navy dark:text-gray-100 font-semibold text-4xl sm:text-5xl"><span class="text-green-600 dark:text-green-400">A calculator</span> your customers can use — anytime</h2>
                <p class="text-gray-500 dark:text-gray-400 my-4 lg:pr-10">Put your calculator on your website and let customers select what they need, see estimated pricing and submit their quote request without having to call or email your team.</p>
            </div>
            <div data-aos="fade-left" data-aos-once="true" class="md:w-1/2 relative bg-gray-900 rounded-2xl p-6 shadow-xl mt-10 md:mt-0">
                <div class="flex items-center space-x-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-red-400"></span>
                    <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                    <span class="w-3 h-3 rounded-full bg-green-400"></span>
                    <span class="ml-3 text-xs text-gray-400">yoursite.com</span>
                </div>
                <div class="bg-white rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">Get an instant quote</p>
                    <div class="space-y-3">
                        <div class="border border-gray-200 rounded-lg px-4 py-3 flex justify-between text-sm">
                            <span class="text-gray-700">Solar panel install</span><span class="font-semibold text-gray-900">Selected</span>
                        </div>
                        <div class="border border-gray-200 rounded-lg px-4 py-3 flex justify-between text-sm">
                            <span class="text-gray-700">Roof type: Shingle</span><span class="font-semibold text-gray-900">+$400</span>
                        </div>
                        <button class="w-full py-3 bg-green-600 text-white font-semibold rounded-lg text-sm">See my price</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Internal tool -->
        <div class="mt-20">
            <div data-aos="fade-up" data-aos-once="true" class="max-w-3xl mx-auto text-center">
                <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 text-sm font-semibold">Internal business tool</div>
                <h2 class="font-semibold text-navy dark:text-gray-100 text-4xl sm:text-5xl">One place to manage, <span class="text-green-600 dark:text-green-400">your quoting</span>, process</h2>
                <p class="text-gray-500 dark:text-gray-400 my-5">Give your team one place to manage products, pricing, options and quotes. Keep everyone working from the same information.</p>
            </div>

            <div data-aos="fade-up" data-aos-once="true" class="w-full mt-10">
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="flex">
                        <aside class="hidden lg:block w-52 bg-gray-900 text-white">
                            <div class="h-16 flex items-center px-5 border-b border-gray-800">
                                <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h3m5 5H7a2 2 0 01-2-2V6a2 2 0 012-2h6l6 6v8a2 2 0 01-2 2z"/></svg>
                                </div>
                                <span class="text-sm font-bold">{{ config('app.name', 'Quotaire') }}</span>
                            </div>
                            <nav class="p-3 space-y-1 text-sm">
                                <a href="#" class="flex items-center px-3 py-2 rounded-lg bg-green-600 text-white">Dashboard</a>
                                <a href="#" class="flex items-center px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-800">Quotes</a>
                                <a href="#" class="flex items-center px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-800">Customers</a>
                                <a href="#" class="flex items-center px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-800">Products</a>
                                <a href="#" class="flex items-center px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-800">Pricing</a>
                            </nav>
                        </aside>

                        <main class="flex-1 bg-gray-50">
                            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">Dashboard</h4>
                                    <p class="text-xs text-gray-500">Here's what's happening with your quotes.</p>
                                </div>
                                <button class="px-4 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg">+ Create new quote</button>
                            </header>

                            <div class="p-6">
                                <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                                        <p class="text-xs text-gray-500">Total quotes</p>
                                        <h5 class="text-2xl font-bold text-gray-900 mt-1">248</h5>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                                        <p class="text-xs text-gray-500">Pending</p>
                                        <h5 class="text-2xl font-bold text-gray-900 mt-1">37</h5>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                                        <p class="text-xs text-gray-500">Approved</p>
                                        <h5 class="text-2xl font-bold text-gray-900 mt-1">126</h5>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                                        <p class="text-xs text-gray-500">Quote value</p>
                                        <h5 class="text-2xl font-bold text-gray-900 mt-1">$184K</h5>
                                    </div>
                                </div>

                                <div class="bg-white rounded-xl border border-gray-200">
                                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                                        <h5 class="font-bold text-gray-900 text-sm">Recent quotes</h5>
                                        <a href="#" class="text-xs font-medium text-green-600">View all &rarr;</a>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-sm">
                                            <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                                                <tr>
                                                    <th class="px-5 py-3">Quote</th>
                                                    <th class="px-5 py-3">Customer</th>
                                                    <th class="px-5 py-3">Amount</th>
                                                    <th class="px-5 py-3">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <tr>
                                                    <td class="px-5 py-3 font-semibold text-gray-800">#1048</td>
                                                    <td class="px-5 py-3 text-gray-700">Michael Brown</td>
                                                    <td class="px-5 py-3 font-semibold text-gray-800">$4,850</td>
                                                    <td class="px-5 py-3"><span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Approved</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="px-5 py-3 font-semibold text-gray-800">#1047</td>
                                                    <td class="px-5 py-3 text-gray-700">Sarah Wilson</td>
                                                    <td class="px-5 py-3 font-semibold text-gray-800">$2,750</td>
                                                    <td class="px-5 py-3"><span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">Pending</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="px-5 py-3 font-semibold text-gray-800">#1046</td>
                                                    <td class="px-5 py-3 text-gray-700">David Miller</td>
                                                    <td class="px-5 py-3 font-semibold text-gray-800">$8,420</td>
                                                    <td class="px-5 py-3"><span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">Sent</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </main>
                    </div>
                </div>
            </div>
        </div>

        <!-- Industry templates CTA -->
        <section class="bg-white dark:bg-gray-900 py-16 lg:py-24 overflow-hidden">
            <div class="relative bg-green-800 rounded-3xl overflow-hidden px-8 py-12 sm:px-12 lg:px-16 lg:py-16" data-aos="fade-up" data-aos-once="true">
                <div class="absolute -top-20 -right-20 w-56 h-56 bg-green-700 rounded-full opacity-60"></div>
                <div class="absolute -bottom-24 -left-16 w-48 h-48 bg-green-900 rounded-full opacity-40"></div>

                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-white bg-opacity-10 text-green-200 text-sm font-semibold">{{ config('app.name', 'Quotaire') }}</div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight text-white">
                        Start fast with <span class="text-green-300">industry templates.</span>
                    </h2>
                    <p class="mt-6 text-lg leading-relaxed text-green-50 max-w-3xl">
                        Why start from a blank page? {{ config('app.name', 'Quotaire') }} comes with ready-made templates for printing, HVAC, cabinets, furniture, manufacturing, and more — each built around real pricing scenarios from that industry. Pick the closest match to your business, then customize every product, question, and price to fit exactly how you quote.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center px-7 py-4 bg-green-500 hover:bg-green-400 text-white font-semibold rounded-full transition duration-300 shadow-lg">
                            Start creating quotes
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('demo') }}" class="inline-flex items-center justify-center px-7 py-4 border border-white border-opacity-30 text-white font-semibold rounded-full hover:bg-white hover:bg-opacity-10 transition duration-300">
                            See how it works
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </div>
@endsection
