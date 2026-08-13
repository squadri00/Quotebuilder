<x-superadmin-layout title="Users">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Users</h2>

            <form method="GET" action="{{ route('superadmin.users.index') }}">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by name or email…"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </form>
        </div>
    </x-slot>

    @if (session('status'))
        <x-card class="mb-4 border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/30">
            <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('status') }}</p>
            @if (session('generatedPassword'))
                <p class="mt-2 text-sm text-green-800 dark:text-green-300">
                    New temporary password (shown once — copy it now):
                    <code class="ml-1 px-2 py-0.5 bg-white dark:bg-gray-800 border border-green-300 dark:border-green-700 rounded text-green-900 dark:text-green-300 font-mono">{{ session('generatedPassword') }}</code>
                </p>
            @endif
        </x-card>
    @endif

    @if ($users->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No users found.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Business</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    @if ($user->business)
                                        <a href="{{ route('superadmin.businesses.show', $user->business) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                            {{ $user->business->name }}
                                        </a>
                                        @if ($user->business->is_template)
                                            <span class="ml-1 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">template</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Deactivated</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <form method="POST" action="{{ route('superadmin.users.reset-password', $user) }}"
                                            onsubmit="return confirm('Reset the password for {{ $user->name }}? A new temporary password will be generated.');">
                                            @csrf
                                            <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Reset Password</button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.users.toggle-active', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif
</x-superadmin-layout>
