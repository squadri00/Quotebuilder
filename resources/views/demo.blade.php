@extends('layouts.public')

@section('title', 'Demo — ' . config('app.name', 'Quotaire'))

@section('content')
    <div class="max-w-6xl mx-auto px-6">

        <section class="pt-16 lg:pt-20 pb-10">
            <div class="max-w-3xl mx-auto text-center" data-aos="fade-up" data-aos-once="true">
                <div class="inline-flex items-center px-4 py-2 mb-6 rounded-full bg-green-50 text-green-700 text-sm font-semibold">
                    See {{ config('app.name', 'Quotaire') }} In Action
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold leading-tight text-gray-900">
                    Try a live quote calculator <span class="text-green-600">built for your industry</span>
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-gray-600">
                    Every business prices differently. That's why we built real, working demo calculators for six industries — so you can click through the exact questions, pricing rules, and instant PDF quote your customers would see. Pick an industry below and try it for yourself.
                </p>
            </div>
        </section>

        <section
            class="py-10 lg:py-16"
            x-data="{
                active: null,
                activeName: null,
                open(slug, name) {
                    this.active = slug;
                    this.activeName = name;
                    this.$nextTick(() => {
                        const container = this.$refs.embedContainer;
                        container.innerHTML = '';
                        const script = document.createElement('script');
                        script.src = '{{ url('/embed.js') }}';
                        script.setAttribute('data-business', slug);
                        container.appendChild(script);
                        this.$refs.embedPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                },
                close() {
                    this.active = null;
                    this.$refs.embedContainer.innerHTML = '';
                },
            }"
        >
            <!-- Inline embedded calculator — same public/embed.js an industry's -->
            <!-- own iframe embed on a real website would use, so this is the -->
            <!-- exact experience a customer gets, right inside the Quotaire layout. -->
            <div x-show="active" x-cloak x-ref="embedPanel" class="mb-10">
                <button type="button" @click="close()" class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-gray-900 mb-4">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    All industries
                </button>
                <div class="rounded-2xl border border-gray-200 shadow-lg overflow-hidden bg-white">
                    <div class="px-5 py-3 bg-gray-900 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-400"></span>
                        <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                        <span class="w-3 h-3 rounded-full bg-green-400"></span>
                        <span class="ml-3 text-xs text-gray-300" x-text="activeName + ' — live demo'"></span>
                    </div>
                    <div x-ref="embedContainer"></div>
                </div>
            </div>

            <div x-show="!active" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($industries as $i => $industry)
                    <div data-aos="fade-up" data-aos-once="true" data-aos-delay="{{ ($i % 3) * 100 }}" class="group flex flex-col bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:-translate-y-1.5 hover:border-green-200 transition duration-300">
                        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-green-600 mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $industry['icon'] }}" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $industry['name'] }}</h3>
                        <p class="text-gray-600 leading-relaxed flex-1">{{ $industry['description'] }}</p>
                        <button type="button" @click="open('{{ $industry['business']->slug }}', '{{ addslashes($industry['name']) }}')" class="inline-flex items-center mt-6 font-semibold text-green-600">
                            Try the demo
                            <svg class="w-4 h-4 ml-1.5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="py-16">
            <div class="relative bg-green-800 rounded-3xl overflow-hidden px-8 py-12 sm:px-12 text-center">
                <div class="absolute -top-20 -right-20 w-56 h-56 bg-green-700 rounded-full opacity-60"></div>
                <div class="absolute -bottom-24 -left-16 w-48 h-48 bg-green-900 rounded-full opacity-40"></div>
                <div class="relative z-10 max-w-2xl mx-auto">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white">Don't see your industry?</h2>
                    <p class="mt-4 text-lg text-green-50">{{ config('app.name', 'Quotaire') }} works for any business that quotes by products, options, or rules. Start from a blank calculator and build it your way in minutes.</p>
                    <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center mt-8 px-7 py-4 bg-green-500 hover:bg-green-400 text-white font-semibold rounded-full transition duration-300 shadow-lg">
                        Start creating quotes
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </section>

    </div>
@endsection
