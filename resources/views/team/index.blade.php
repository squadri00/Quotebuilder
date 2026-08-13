<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Team</h2>
            <x-primary-button onclick="window.location='{{ route('team.create') }}'">Invite Teammate</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        Every teammate is a Member — grant exactly the products and other areas of the app they need below. Team and Billing are always Owner-only.
    </p>

    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Members</h3>
    <x-card class="p-0 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Products</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Other Access</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($members as $member)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                {{ $member->name }}
                                @if ($member->isOwner())
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300 ml-1">Owner</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $member->email }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                @if ($member->isOwner())
                                    All products
                                @elseif ($member->products->isEmpty())
                                    <span class="text-amber-600 dark:text-amber-400">None granted</span>
                                @else
                                    {{ $member->products->pluck('name')->join(', ') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                @if ($member->isOwner())
                                    All
                                @else
                                    @php $granted = collect($member->permissions ?? [])->map(fn ($key) => \App\Support\TeamPermissions::all()[$key]['label'] ?? $key); @endphp
                                    {{ $granted->isEmpty() ? 'None granted' : $granted->join(', ') }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($member->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Deactivated</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-3">
                                @unless ($member->isOwner())
                                    <a href="{{ route('team.edit', $member) }}" class="text-sm font-medium brand-text">Edit</a>
                                    <form method="POST" action="{{ route('team.toggle-active', $member) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                            {{ $member->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Account owner</span>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($invitations->isNotEmpty())
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Pending Invitations</h3>
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Access</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($invitations as $invitation)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $invitation->name }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $invitation->email }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    @php
                                        $productCount = count($invitation->product_ids ?? []);
                                        $permCount = count($invitation->permissions ?? []);
                                    @endphp
                                    {{ $productCount }} product{{ $productCount === 1 ? '' : 's' }}, {{ $permCount }} other area{{ $permCount === 1 ? '' : 's' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($invitation->isExpired())
                                        <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Expired</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">Pending</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-3">
                                    <form method="POST" action="{{ route('team.invitations.resend', $invitation) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium brand-text">Resend</button>
                                    </form>
                                    <form method="POST" action="{{ route('team.invitations.revoke', $invitation) }}" class="inline"
                                        onsubmit="return confirm('Revoke this invitation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-app-layout>
