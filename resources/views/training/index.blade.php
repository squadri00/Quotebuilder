<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Training Library</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($artifacts->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            Nothing here yet — a tutorial appears automatically here for each of your products that has one.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tutorial</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Installed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($artifacts as $artifact)
                            <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40" onclick="window.location='{{ route('training.show', $artifact) }}'">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $artifact->product->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $artifact->title }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $artifact->created_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-app-layout>
