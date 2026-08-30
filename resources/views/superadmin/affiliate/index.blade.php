@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-superadmin-layout title="Referral Partners">
    <x-slot name="header">
        <div class="flex w-full items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Referral Partners</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.affiliate.partners.create') }}'">New Partner</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
        @foreach ([
            ['Active partners', $overview['partners_active'], $overview['partners_pending'] . ' awaiting approval'],
            ['Approved referrals', $overview['referrals_approved'], $overview['referrals_pending'] . ' pending review'],
            ['Active claims', $overview['claims_active'], 'territory locks in force'],
            ['Unpaid commission', $m($overview['liability_unpaid']), $m($overview['liability_pending']) . ' pending'],
            ['Statements due', $m($overview['payouts_due']), $m($overview['payouts_paid_ytd']) . ' paid YTD'],
        ] as [$l, $val, $sub])
            <x-card class="!p-4">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ $l }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $val }}</div>
                <div class="text-xs text-gray-400">{{ $sub }}</div>
            </x-card>
        @endforeach
    </div>

    <x-card class="!p-0 overflow-hidden">
        <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-4 dark:border-gray-700">
            <input name="search" value="{{ $search }}" placeholder="Name, email, code, company…"
                   class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
            <select name="status" class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                <option value="">All statuses</option>
                @foreach (['pending', 'active', 'suspended', 'rejected'] as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        @foreach (['Partner', 'Code', 'Rate', 'Referrals', 'Status', 'Joined', ''] as $h)
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($partners as $p)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('superadmin.affiliate.partners.show', $p) }}" class="font-medium text-indigo-600 dark:text-indigo-400">{{ $p->name }}</a>
                                <div class="text-xs text-gray-400">{{ $p->email }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $p->partner_code }}</td>
                            <td class="px-4 py-3">{{ rtrim(rtrim(number_format((float) $p->commission_rate, 3), '0'), '.') }}%</td>
                            <td class="px-4 py-3">{{ $p->approved_referrals }}</td>
                            <td class="px-4 py-3"><x-affiliate-badge :status="$p->status" /></td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('superadmin.affiliate.partners.show', $p) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No partners yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $partners->links() }}</div>
    </x-card>
</x-superadmin-layout>
