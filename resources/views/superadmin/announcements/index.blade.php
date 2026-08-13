<x-superadmin-layout title="Announcements">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Announcements</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.announcements.create') }}'">New Announcement</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($announcements->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No announcements yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Severity</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Audience</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Created</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($announcements as $announcement)
                            @php
                                $sevColor = match ($announcement->severity) {
                                    'critical' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                                    'warning' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                                    default => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                                };
                                $audienceLabel = match ($announcement->target_audience) {
                                    'with_plan' => 'With a plan',
                                    'no_plan' => 'Without a plan',
                                    'specific' => $announcement->targetBusiness?->name ?? 'Specific business',
                                    default => 'All businesses',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('superadmin.announcements.edit', $announcement) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                        {{ $announcement->title }}
                                    </a>
                                    @if ($announcement->isExpired())
                                        <span class="ml-1 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:text-gray-400">Expired</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sevColor }}">{{ ucfirst($announcement->severity) }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $audienceLabel }}</td>
                                <td class="px-4 py-3">
                                    @if ($announcement->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Withdrawn</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $announcement->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('superadmin.announcements.toggle-active', $announcement) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                            {{ $announcement->is_active ? 'Withdraw' : 'Reactivate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $announcements->links() }}
        </div>
    @endif
</x-superadmin-layout>
