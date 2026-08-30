@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-superadmin-layout title="Affiliate Commissions">
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Commissions</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 flex gap-1 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('superadmin.affiliate.commissions.index', ['tab' => 'referrals']) }}"
           class="border-b-2 px-4 py-2 text-sm font-semibold {{ $tab === 'referrals' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500' }}">
            Referral approvals @if($pendingReferrals->count())({{ $pendingReferrals->count() }})@endif
        </a>
        <a href="{{ route('superadmin.affiliate.commissions.index', ['tab' => 'ledger']) }}"
           class="border-b-2 px-4 py-2 text-sm font-semibold {{ $tab === 'ledger' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500' }}">
            Commission ledger
        </a>
    </div>

    @if ($tab === 'referrals')
        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                    @foreach (['Business', 'Partner', 'Source', 'Accrued (pending)', 'Created', 'Actions'] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($pendingReferrals as $r)
                    <tr>
                        <td class="px-4 py-3">{{ $r->business?->name }}<div class="text-xs text-gray-400">#{{ $r->business_id }}</div></td>
                        <td class="px-4 py-3"><a href="{{ route('superadmin.affiliate.partners.show', $r->partner_id) }}" class="text-indigo-600">{{ $r->partner?->name }}</a><div class="text-xs text-gray-400">{{ $r->partner?->partner_code }}</div></td>
                        <td class="px-4 py-3">{{ $r->source }}</td>
                        <td class="px-4 py-3">{{ $r->accrued_lines }} lines · {{ $m($r->accrued_total) }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $r->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                <form method="POST" action="{{ route('superadmin.affiliate.referrals.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="approve"><button class="rounded bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white">Approve</button></form>
                                <form method="POST" action="{{ route('superadmin.affiliate.referrals.update', $r) }}" onsubmit="return confirm('Reject this referral? Its commissions will be voided.')">@csrf @method('PATCH')<input type="hidden" name="action" value="reject"><button class="rounded border border-gray-300 px-2.5 py-1 text-xs dark:border-gray-600">Reject</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Nothing pending.</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-card>
    @else
        <x-card class="mb-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Add manual adjustment / clawback</h3>
            <form method="POST" action="{{ route('superadmin.affiliate.adjustments.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label value="Referral" />
                    <select name="referral_id" required class="mt-1 rounded-md border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                        <option value="">Select…</option>
                        @foreach ($approvedRefs as $ar)<option value="{{ $ar->id }}">{{ $ar->business?->name }} ({{ $ar->partner?->partner_code }})</option>@endforeach
                    </select>
                </div>
                <div><x-input-label value="Kind" /><select name="kind" class="mt-1 rounded-md border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100"><option value="adjustment">Adjustment</option><option value="clawback">Clawback</option></select></div>
                <div><x-input-label value="Amount (− for deduction)" /><x-text-input name="amount" type="number" step="0.01" class="mt-1 w-32" required /></div>
                <div class="flex-1"><x-input-label value="Note" /><x-text-input name="note" class="mt-1 block w-full" /></div>
                <x-primary-button>Add line</x-primary-button>
            </form>
        </x-card>

        <x-card class="!p-0 overflow-hidden">
            <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-4 dark:border-gray-700">
                <input type="hidden" name="tab" value="ledger">
                <select name="status" class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'approved', 'on_payout', 'paid', 'void'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach
                </select>
                <select name="partner" class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                    <option value="">All partners</option>
                    @foreach ($partners as $pp)<option value="{{ $pp->id }}" @selected($partnerFilter === $pp->id)>{{ $pp->partner_code }}</option>@endforeach
                </select>
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                        @foreach (['Period', 'Partner', 'Business', 'Kind', 'Base', 'Rate', 'Amount', 'Status', 'Statement', ''] as $h)<th class="px-3 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($ledger as $c)
                        <tr>
                            <td class="px-3 py-2">{{ sprintf('%04d-%02d', $c->period_year, $c->period_month) }}</td>
                            <td class="px-3 py-2">{{ $c->partner?->partner_code }}</td>
                            <td class="px-3 py-2">{{ $c->business?->name ?? $c->business_name_snapshot }}</td>
                            <td class="px-3 py-2">{{ $c->kind }}</td>
                            <td class="px-3 py-2">{{ $c->kind === 'commission' ? $m($c->base_amount) : '—' }}</td>
                            <td class="px-3 py-2">{{ $c->kind === 'commission' ? rtrim(rtrim(number_format((float) $c->rate, 3), '0'), '.') . '%' : '—' }}</td>
                            <td class="px-3 py-2 font-semibold {{ $c->commission_amount < 0 ? 'text-red-600' : '' }}">{{ $m($c->commission_amount) }}</td>
                            <td class="px-3 py-2"><x-affiliate-badge :status="$c->status" /></td>
                            <td class="px-3 py-2 text-gray-400">{{ $c->payout?->payout_number ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($c->payout_id === null && $c->status !== 'paid')
                                    <form method="POST" action="{{ route('superadmin.affiliate.commissions.update', $c) }}" class="flex gap-1">@csrf @method('PATCH')
                                        @if ($c->status !== 'approved')<button name="status" value="approved" class="rounded border border-gray-300 px-2 py-0.5 text-xs dark:border-gray-600">Approve</button>@endif
                                        @if ($c->status !== 'void')<button name="status" value="void" class="rounded border border-gray-300 px-2 py-0.5 text-xs dark:border-gray-600">Void</button>
                                        @else<button name="status" value="pending" class="rounded border border-gray-300 px-2 py-0.5 text-xs dark:border-gray-600">Restore</button>@endif
                                    </form>
                                @else <span class="text-gray-400">locked</span> @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-10 text-center text-gray-400">No commission lines.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $ledger->links() }}</div>
        </x-card>
    @endif
</x-superadmin-layout>
