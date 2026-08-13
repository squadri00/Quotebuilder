<x-superadmin-layout title="{{ $artifact->title }}">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $artifact->title }}</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('superadmin.training.raw', $artifact) }}" target="_blank" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Open in new tab</a>
                <a href="{{ route('superadmin.training.edit', $artifact) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">Edit</a>
                <form method="POST" action="{{ route('superadmin.training.destroy', $artifact) }}" onsubmit="return confirm('Delete \'{{ $artifact->title }}\'? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800">Delete</button>
                </form>
            </div>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="mb-3 text-xs text-gray-400 dark:text-gray-500">
        @if ($artifact->industry)
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 mr-2">{{ $artifact->industry->name }}</span>
        @endif
        Added {{ $artifact->created_at->format('M j, Y') }}{{ $artifact->creator ? ' by '.$artifact->creator->name : '' }}
        @if ($artifact->updated_at->ne($artifact->created_at))
            &middot; updated {{ $artifact->updated_at->diffForHumans() }}
        @endif
    </p>

    <x-card :padding="false" class="overflow-hidden">
        <iframe
            src="{{ route('superadmin.training.raw', $artifact) }}"
            class="w-full border-0"
            style="height: 78vh;"
            sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
        ></iframe>
    </x-card>
</x-superadmin-layout>
