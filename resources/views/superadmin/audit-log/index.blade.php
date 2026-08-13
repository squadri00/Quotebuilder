<x-superadmin-layout title="Audit Log">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Audit Log</h2>
    </x-slot>

    @if ($logs->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No admin actions have been logged yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">When</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Admin</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Action</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->created_at->format('M j, Y g:ia') }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $log->admin?->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $log->action }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $log->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</x-superadmin-layout>
