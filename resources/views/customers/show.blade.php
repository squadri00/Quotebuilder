<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $customer->name }}</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('customers.edit', $customer) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Edit</a>
            </div>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Contact</h3>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $customer->email }}</dd>
                </div>
                @if ($customer->phone)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $customer->phone }}</dd>
                    </div>
                @endif
                @if (count($customer->addressLines()))
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Address</dt>
                        <dd class="text-gray-900 dark:text-gray-100">
                            @foreach ($customer->addressLines() as $line)
                                <div>{{ $line }}</div>
                            @endforeach
                        </dd>
                    </div>
                @endif
                @if ($customer->notes)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Notes</dt>
                        <dd class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $customer->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <div class="lg:col-span-2">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Quote History</h3>

            @if ($quotes->isEmpty())
                <x-card class="text-center text-gray-500 dark:text-gray-400">
                    No quotes yet.
                </x-card>
            @else
                <x-card class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Price</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($quotes as $quote)
                                    <tr @if ($quote->isInternal()) class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40" onclick="window.location='{{ route('quotes.create.result', $quote) }}'" @endif>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $quote->created_at->format('M j, Y') }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $quote->product?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">${{ number_format($quote->final_price, 2) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $quote->isInternal() ? 'Internal' : 'Public' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <div class="mt-4">
                    {{ $quotes->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
