@props(['bg' => 'bg-gray-50', 'title'])

<div {{ $attributes->merge(['class' => "$bg rounded-2xl p-8 flex flex-col"]) }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/70 text-indigo-700">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-6 w-6">
            {{ $icon }}
        </svg>
    </span>

    <h3 class="mt-5 text-xl font-extrabold text-gray-900">{{ $title }}</h3>

    <p class="mt-2.5 text-sm text-gray-600 leading-relaxed">
        {{ $slot }}
    </p>
</div>
