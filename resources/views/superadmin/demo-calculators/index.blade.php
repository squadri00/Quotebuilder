<x-superadmin-layout title="Demo Calculators">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Demo Calculators</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.demo-calculators.create') }}'">New Demo Calculator</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        The industry cards on the public <a href="{{ url('/demo') }}" target="_blank" class="underline hover:no-underline">Demo page</a> — each opens the linked business's live Quote Hub inline. Only active ones show up there, and the order below is the order they appear in.
    </p>

    @if ($demoCalculators->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No demo calculators yet — add one to feature it on the public Demo page.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Order</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Card</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Links to</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($demoCalculators as $demoCalculator)
                            <tr>
                                <td class="px-4 py-3 text-gray-400 dark:text-gray-500">{{ $demoCalculator->sort_order }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $demoCalculator->icon }}" /></svg>
                                        </span>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $demoCalculator->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md truncate">{{ $demoCalculator->description }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $demoCalculator->business?->name ?? '— business deleted —' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $demoCalculator->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $demoCalculator->is_active ? 'Live' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-4">
                                        <form method="POST" action="{{ route('superadmin.demo-calculators.toggle-active', $demoCalculator) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                {{ $demoCalculator->is_active ? 'Hide' : 'Make live' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('superadmin.demo-calculators.edit', $demoCalculator) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                                        <form method="POST" action="{{ route('superadmin.demo-calculators.destroy', $demoCalculator) }}"
                                            onsubmit="return confirm('Delete &quot;{{ $demoCalculator->name }}&quot;? This only removes the card from the Demo page — the business itself is untouched.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-superadmin-layout>
