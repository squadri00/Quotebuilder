@props(['active' => false])

@php
$classes = ($active ?? false)
    ? 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium bg-gray-800 text-white'
    : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <span class="flex h-5 w-5 shrink-0 items-center justify-center">{{ $icon ?? '' }}</span>
    <span x-show="sidebarOpen" x-cloak>{{ $slot }}</span>
</a>
