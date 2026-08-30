@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-superadmin-layout title="Payout Statements">
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Payout Statements</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <x-card class="mb-5">
        <h3 class="mb-3 text-sm font-semibold">Generate a statement</h3>
        @if ($candidates->isEmpty())
            <p class="text-sm text-gray-400">No partner has approved, un-invoiced commission right now.</p>
        @else
            <form method="POST" action="{{ route('superadmin.affiliate.payouts.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label value="Partner & month" />
                    <select required onchange="var o=this.selectedOptions[0];this.form.partner_id.value=o.dataset.p;this.form.year.value=o.dataset.y;this.form.month.value=o.dataset.mm;"
                            class="mt-1 rounded-md border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                        <option value="">Select…</option>
                        @foreach ($candidates as $cd)
                            <option data-p="{{ $cd->partner_id }}" data-y="{{ $cd->period_year }}" data-mm="{{ $cd->period_month }}">
                                {{ $cd->partner_code }} — {{ \Carbon\Carbon::create($cd->period_year, $cd->period_month, 1)->format('M Y') }}
                                ({{ $cd->line_count }} lines, {{ $m($cd->amount) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="partner_id"><input type="hidden" name="year"><input type="hidden" name="month">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="skip_minimum" value="1" class="rounded border-gray-300"> Ignore minimum payout</label>
                <x-primary-button>Generate</x-primary-button>
            </form>
        @endif
    </x-card>

    <x-card class="!p-0 overflow-hidden">
        <form method="GET" class="flex items-center gap-2 border-b border-gray-200 p-4 dark:border-gray-700">
            <select name="status" class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                <option value="">All statuses</option>
                @foreach (['draft', 'finalized', 'submitted', 'paid', 'void'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                    @foreach (['Number', 'Partner', 'Period', 'Lines', 'Amount', 'Status', 'Paid', ''] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($payouts as $p)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $p->payout_number }}</td>
                        <td class="px-4 py-3">{{ $p->partner?->partner_code }}</td>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::create($p->period_year, $p->period_month, 1)->format('M Y') }}</td>
                        <td class="px-4 py-3">{{ $p->commission_count }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $m($p->amount) }} {{ $p->currency }}</td>
                        <td class="px-4 py-3"><x-affiliate-badge :status="$p->status" /></td>
                        <td class="px-4 py-3 text-gray-400">{{ $p->paid_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('superadmin.affiliate.payouts.show', $p) }}" class="text-indigo-600">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">No statements yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $payouts->links() }}</div>
    </x-card>
</x-superadmin-layout>
