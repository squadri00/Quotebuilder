<x-superadmin-layout title="Features">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Features</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.features.create') }}'">New Feature</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($features->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No features yet. Features are the gate-able capabilities plans can include (e.g. PDF downloads, email notifications).
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Key</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Description</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($features as $feature)
                            <tr>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $feature->name }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $feature->key }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $feature->description }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-4">
                                        <a href="{{ route('superadmin.features.edit', $feature) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                                        <form method="POST" action="{{ route('superadmin.features.destroy', $feature) }}"
                                            onsubmit="return confirm('Delete feature &quot;{{ $feature->name }}&quot;? It will be removed from any plans that include it.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-superadmin-layout>
