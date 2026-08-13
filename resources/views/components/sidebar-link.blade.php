@props(['active' => false, 'collapsedLabel' => null])

@php
$classes = ($active ?? false)
    ? 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium brand-active-nav'
    : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <span class="flex h-5 w-5 shrink-0 items-center justify-center">{{ $icon ?? '' }}</span>
    <span x-show="sidebarOpen" x-cloak>{{ $slot }}</span>
</a>
