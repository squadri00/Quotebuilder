<x-superadmin-layout title="Implementation Tiers">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Implementation Tiers</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.implementation-tiers.create') }}'">New Tier</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($tiers->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No implementation tiers yet.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($tiers as $tier)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $tier->name }}</p>
                            @if ($tier->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Deactivated</span>
                            @endif
                            @if ($tier->stripe_price_id)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-400 font-mono">{{ $tier->stripe_price_id }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">No Stripe price</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $tier->product_count }} products &middot; ${{ number_format($tier->price, 2) }} one-time &middot; {{ $tier->orders_count }} order(s)
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <form method="POST" action="{{ route('superadmin.implementation-tiers.toggle-active', $tier) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                {{ $tier->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <a href="{{ route('superadmin.implementation-tiers.edit', $tier) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Edit</a>
                        <form method="POST" action="{{ route('superadmin.implementation-tiers.destroy', $tier) }}"
                            onsubmit="return confirm('Delete tier &quot;{{ $tier->name }}&quot;? Existing orders keep their own record of what was purchased.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
