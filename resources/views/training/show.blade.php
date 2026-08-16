<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $artifact->title }}</h2>
            <a href="{{ route('training.raw', $artifact) }}" target="_blank" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Open in new tab</a>
        </div>
    </x-slot>

    <p class="mb-3 text-xs text-gray-400 dark:text-gray-500">
        @if ($artifact->product)
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 mr-2">{{ $artifact->product->name }}</span>
        @endif
        Installed {{ $artifact->created_at->format('M j, Y') }}
    </p>

    <x-card :padding="false" class="overflow-hidden">
        <iframe
            src="{{ route('training.raw', $artifact) }}"
            class="w-full border-0"
            style="height: 78vh;"
            sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
        ></iframe>
    </x-card>
</x-app-layout>
