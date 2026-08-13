<x-superadmin-layout title="Support Tickets">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Support Tickets</h2>

            <form method="GET" action="{{ route('superadmin.support.index') }}">
                <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\SupportTicket::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    @if ($tickets->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No support tickets{{ request('status') ? ' with that status' : '' }}.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tracking #</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Business</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Subject</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($tickets as $ticket)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" onclick="window.location='{{ route('superadmin.support.show', $ticket) }}'">
                                <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $ticket->tracking_number }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $ticket->business->name }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ticket->subject }}</td>
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
</x-superadmin-layout>
