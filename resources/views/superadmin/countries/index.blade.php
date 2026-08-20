<x-superadmin-layout title="Countries">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Countries</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.countries.create') }}'">New Country</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Which countries a business can register from, and the currency shown alongside their price for each. Only active countries appear on the registration form.
    </p>

    @if ($countries->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No countries yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Country</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Code</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Currency</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Example</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($countries as $country)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $country->name }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $country->short_code }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $country->currency_code }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $country->currency_position === 'before' ? $country->currency_symbol.'29' : '29'.$country->currency_symbol }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $country->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $country->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-4">
                                        <form method="POST" action="{{ route('superadmin.countries.toggle-active', $country) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                {{ $country->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('superadmin.countries.edit', $country) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                                        <form method="POST" action="{{ route('superadmin.countries.destroy', $country) }}"
                                            onsubmit="return confirm('Delete &quot;{{ $country->name }}&quot;? Businesses already registered from here keep their data.');">
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
