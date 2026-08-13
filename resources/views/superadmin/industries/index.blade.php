<x-superadmin-layout title="Industries">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Industries</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.industries.create') }}'">New Industry</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Shown on the registration page so a new business can identify what they do — matching templates are then recommended to them.
    </p>

    @if ($industries->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No industries yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Businesses / Templates</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Template Products</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($industries as $industry)
                            <tr>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $industry->name }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $industry->businesses_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($industry->templates->isEmpty())
                                        <span class="text-gray-400 dark:text-gray-500">No template yet</span>
                                    @else
                                        <div class="flex flex-col gap-1">
                                            @foreach ($industry->templates as $template)
                                                <a href="{{ route('superadmin.products.index', $template) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                                                    {{ $industry->templates->count() > 1 ? $template->name : 'View Products' }} &rarr;
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-4">
                                        <a href="{{ route('superadmin.industries.edit', $industry) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                                        <form method="POST" action="{{ route('superadmin.industries.destroy', $industry) }}"
                                            onsubmit="return confirm('Delete industry &quot;{{ $industry->name }}&quot;? Businesses/templates using it keep their data, but will show no industry until you assign a new one.');">
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
