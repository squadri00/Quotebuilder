<x-superadmin-layout title="Training Library">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Training Library</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.training.create') }}'">New Artifact</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="GET" action="{{ route('superadmin.training.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
        <x-text-input type="text" name="search" value="{{ $search }}" placeholder="Search by title…" class="w-full max-w-sm" />

        <select name="industry_id" onchange="this.form.submit()"
            class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
            <option value="">All industries</option>
            @foreach ($industries as $industry)
                <option value="{{ $industry->id }}" @selected((string) $industryId === (string) $industry->id)>{{ $industry->name }}</option>
            @endforeach
        </select>

        @if ($search !== '' || $industryId !== '')
            <a href="{{ route('superadmin.training.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Clear</a>
        @endif
    </form>

    @if ($artifacts->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            @if ($search !== '' || $industryId !== '')
                Nothing matches those filters.
            @else
                Nothing here yet — add your first training artifact.
            @endif
        </x-card>
    @else
        @foreach ($artifacts as $groupName => $group)
            <div class="mb-8">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $groupName }} <span class="font-normal normal-case text-gray-400 dark:text-gray-500">({{ $group->count() }})</span>
                </h3>

                <x-card class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Added</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">By</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($group as $artifact)
                                    <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40" onclick="window.location='{{ route('superadmin.training.show', $artifact) }}'">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $artifact->title }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $artifact->created_at->format('M j, Y') }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $artifact->creator?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('superadmin.training.edit', $artifact) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">Edit</a>
                                                <form method="POST" action="{{ route('superadmin.training.destroy', $artifact) }}" onsubmit="return confirm('Delete \'{{ $artifact->title }}\'? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        @endforeach
    @endif
</x-superadmin-layout>
