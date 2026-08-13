<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Customers</h2>
            <x-primary-button onclick="window.location='{{ route('customers.create') }}'">New Customer</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="GET" action="{{ route('customers.index') }}" class="mb-4">
        <x-text-input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email, or phone…" class="w-full max-w-sm" />
    </form>

    @if ($customers->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            @if ($search !== '')
                No customers match "{{ $search }}".
            @else
                No customers yet — they're added automatically the first time you quote someone, or add one yourself.
            @endif
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Phone</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Quotes</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($customers as $customer)
                            <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40" onclick="window.location='{{ route('customers.show', $customer) }}'">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $customer->email }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $customer->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $customer->quotes_count }}</td>
                                <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                    <a href="{{ route('customers.edit', $customer) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    @endif
</x-app-layout>
