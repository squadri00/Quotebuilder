<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Support</h2>
            <x-primary-button onclick="window.location='{{ route('support.create') }}'">New Ticket</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($tickets->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No support tickets yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tracking #</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Subject</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($tickets as $ticket)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer" onclick="window.location='{{ route('support.show', $ticket) }}'">
                                <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $ticket->tracking_number }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $ticket->subject }}</td>
                                <td class="px-4 py-3">
                                    <x-support-status-badge :status="$ticket->status" />
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $ticket->updated_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $tickets->links() }}
        </div>
    @endif
</x-app-layout>
