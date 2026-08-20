<x-superadmin-layout title="Implementation Orders">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Implementation Orders</h2>

            <form method="GET" action="{{ route('superadmin.implementation-orders.index') }}">
                <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\ImplementationOrder::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($abandonedCount > 0)
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            {{ $abandonedCount }} abandoned {{ Str::plural('checkout', $abandonedCount) }} hidden (never paid, Stripe's checkout link expired) —
            <a href="{{ route('superadmin.implementation-orders.index', ['status' => 'abandoned']) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">view them</a>.
        </p>
    @endif

    @if ($orders->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No orders{{ request('status') ? ' with that status' : '' }} yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Business</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Package</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Price</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tax</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Total Charged</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Purchased</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $order->business->name }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $order->tier_name }} ({{ $order->product_count }} products)</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">${{ number_format($order->price, 2) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    @if ($order->tax_amount > 0)
                                        {{ $order->tax_label }} {{ rtrim(rtrim(number_format($order->tax_rate, 3), '0'), '.') }}% — ${{ number_format($order->tax_amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">${{ number_format($order->totalCharged(), 2) }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $order->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('superadmin.implementation-orders.update-status', $order) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()"
                                            class="rounded-lg border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach (\App\Models\ImplementationOrder::STATUSES as $status)
                                                <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif
</x-superadmin-layout>
