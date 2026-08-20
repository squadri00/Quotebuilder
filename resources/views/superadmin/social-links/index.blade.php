<x-superadmin-layout title="Social Links">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Social Links</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.social-links.create') }}'">New Social Link</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        The social icon buttons shown in the site footer. Only active ones show up there, in the order below.
    </p>

    @if ($socialLinks->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No social links yet — add one to show it in the footer.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Order</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Platform</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Link</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($socialLinks as $socialLink)
                            @php $icon = $socialLink->icon(); @endphp
                            <tr>
                                <td class="px-4 py-3 text-gray-400 dark:text-gray-500">{{ $socialLink->sort_order }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="{{ $icon['viewBox'] }}" fill="currentColor"><path d="{{ $icon['path'] }}" /></svg>
                                        </span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $socialLink->displayLabel() }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ $socialLink->url }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline max-w-xs truncate inline-block align-bottom">{{ $socialLink->url }}</a>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $socialLink->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $socialLink->is_active ? 'Live' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-4">
                                        <form method="POST" action="{{ route('superadmin.social-links.toggle-active', $socialLink) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                {{ $socialLink->is_active ? 'Hide' : 'Make live' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('superadmin.social-links.edit', $socialLink) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                                        <form method="POST" action="{{ route('superadmin.social-links.destroy', $socialLink) }}"
                                            onsubmit="return confirm('Delete &quot;{{ $socialLink->displayLabel() }}&quot;?');">
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
