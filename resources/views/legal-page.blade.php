@extends('layouts.public')

@section('title', $page->title . ' — ' . config('app.name', 'Quotaire'))

@section('content')
    <section class="max-w-3xl mx-auto px-6 pt-16 sm:pt-20 pb-24">
        <div data-aos="fade-up" data-aos-once="true">
            <h1 class="text-4xl sm:text-5xl font-bold leading-tight text-gray-900 dark:text-gray-100">{{ $page->title }}</h1>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
        </div>

        <div class="mt-10 space-y-5 text-gray-700 dark:text-gray-300 leading-relaxed" data-aos="fade-up" data-aos-once="true">
            @foreach (preg_split('/\n\s*\n/', trim($page->content)) as $block)
                @php $block = trim($block); @endphp
                @if (str_starts_with($block, '## '))
                    <h2 class="pt-4 text-xl font-bold text-gray-900 dark:text-gray-100">{{ substr($block, 3) }}</h2>
                @else
                    <p class="whitespace-pre-line">{{ $block }}</p>
                @endif
            @endforeach
        </div>
    </section>
@endsection
