<x-superadmin-layout :title="$business->name.' — Rules'">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('superadmin.products.index', $business) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $business->name }}</a> / Quote Builder
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Rules</h2>
            </div>
            <x-primary-button onclick="window.location='{{ route('superadmin.rules.create', $business) }}'">New Rule</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($rules->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No rules yet. Rules let you automatically adjust a product's price based on the customer's answers.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($rules as $item)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $item['rule']->name }}</p>
                            @unless ($item['rule']->is_published)
                                <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">Unpublished</span>
                            @endunless
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $item['sentence'] }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Product: {{ $item['rule']->product?->name ?? '—' }}
                        </p>
                    </div>
                    <a href="{{ route('superadmin.rules.edit', $item['rule']) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
