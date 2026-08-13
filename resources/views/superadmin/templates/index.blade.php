<x-superadmin-layout title="Templates">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Templates</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.templates.create') }}'">New Template</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($templates->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No templates yet. Create one so new businesses have a starting catalog to clone.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($templates as $template)
                <x-card class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $template->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $template->industry?->name ?? 'No industry set' }} &middot; {{ $template->products_count }} product(s) &middot; {{ $template->rules_count }} rule(s)</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('superadmin.products.index', $template) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Quote Builder</a>
                        <a href="{{ route('superadmin.templates.edit', $template) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                        <form method="POST" action="{{ route('superadmin.templates.destroy', $template) }}"
                            onsubmit="return confirm('Delete template &quot;{{ $template->name }}&quot;? Businesses already created from it keep their own copy of the data — this only removes the template itself.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
