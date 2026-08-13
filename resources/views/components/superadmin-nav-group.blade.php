@props(['title', 'active' => false])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button
        type="button"
        @click="open = !open"
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition duration-150 ease-in-out"
    >
        <span class="flex h-5 w-5 shrink-0 items-center justify-center">{{ $icon ?? '' }}</span>
        <span x-show="sidebarOpen" x-cloak class="flex-1 text-left whitespace-nowrap">{{ $title }}</span>
        <svg x-show="sidebarOpen" x-cloak :class="open && 'rotate-90'" class="h-4 w-4 shrink-0 transition-transform duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="mt-1 space-y-1 border-l border-gray-800 pl-3" :class="sidebarOpen ? 'ml-5' : 'ml-0 border-l-0 pl-0'">
        {{ $slot }}
    </div>
</div>
