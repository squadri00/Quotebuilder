<x-superadmin-layout title="Site Pages">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Site Pages</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.site-pages.create') }}'">New Page</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Content for public pages like Privacy Policy and Terms of Use — the footer links to whichever pages exist at the slugs <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">privacy-policy</code> and <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">terms-of-use</code>. Each page's "Last updated" date on the public site is always just the date it was last saved here.
    </p>

    @if ($sitePages->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No pages yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Page</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">URL</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Last Updated</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($sitePages as $sitePage)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $sitePage->title }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('legal.show', $sitePage->slug) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">/legal/{{ $sitePage->slug }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $sitePage->updated_at->format('F j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-4">
                                        <a href="{{ route('superadmin.site-pages.edit', $sitePage) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                                        <form method="POST" action="{{ route('superadmin.site-pages.destroy', $sitePage) }}"
                                            onsubmit="return confirm('Delete &quot;{{ $sitePage->title }}&quot;? Any link to it on the site will 404 until you replace it.');">
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
