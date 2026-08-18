<x-superadmin-layout title="Businesses">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Businesses</h2>

            <div class="flex items-center gap-4">
                <a href="{{ route('superadmin.exports.businesses') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Export CSV</a>

                <form method="GET" action="{{ route('superadmin.businesses.index') }}">
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name…"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </form>
            </div>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($businesses->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No businesses found.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Plan</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Industry</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Template used</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Signed up</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($businesses as $business)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('superadmin.businesses.show', $business) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                        {{ $business->name }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $business->users_count }} user(s) &middot; {{ $business->products_count }} product(s) &middot; {{ $business->quotes_count }} quote(s)</p>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $business->owner?->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    @if ($business->plan)
                                        {{ $business->plan->name }}
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">No plan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $business->industry?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $business->createdFromTemplate?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $business->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    @if ($business->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Deactivated</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('superadmin.businesses.show', $business) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $businesses->links() }}
        </div>
    @endif
</x-superadmin-layout>
