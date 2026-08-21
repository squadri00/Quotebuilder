@props(['bind'])

{{--
    A small "i" icon shown next to a wizard question/option, driven by an
    Alpine expression (e.g. "question.description") rather than a static
    string prop, since question/option data here comes from a runtime JS
    array, not Blade. Hidden entirely when that field is empty.
--}}
<span
    x-data="{ open: false }"
    x-show="{{ $bind }}"
    @mouseenter="open = true"
    @mouseleave="open = false"
    @click.outside="open = false"
    class="relative inline-flex shrink-0"
>
    <button
        type="button"
        @click.stop.prevent="open = !open"
        class="flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold leading-none text-gray-600 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
        aria-label="More information"
    >i</button>
    <span
        x-show="open"
        x-transition.opacity
        x-cloak
        x-text="{{ $bind }}"
        class="pointer-events-none absolute z-30 bottom-full left-1/2 mb-2 w-56 max-w-[70vw] -translate-x-1/2 rounded-lg bg-gray-900 px-3 py-2 text-left text-xs font-normal normal-case leading-snug text-white shadow-lg"
    ></span>
</span>
