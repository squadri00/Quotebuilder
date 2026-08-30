@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-superadmin-layout :title="'Statement ' . $payout->payout_number">
    <x-slot name="header">
        <div class="flex w-full items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $payout->payout_number }}</h2>
            <a href="{{ route('affiliate.portal.statements.show', $payout) }}" target="_blank"
               class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">Printable</a>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card>
            <div class="text-xs text-gray-400">Partner</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $payout->partner->name }}
                <span class="rounded bg-orange-100 px-2 py-0.5 font-mono text-xs text-orange-700">{{ $payout->partner->partner_code }}</span></div>
            <div class="text-xs text-gray-400">{{ $payout->partner->email }}</div>
            <div class="mt-2 text-xs text-gray-400">Payout method: {{ $payout->partner->payout_method ?: '—' }}</div>
            <div class="whitespace-pre-wrap text-xs text-gray-400">{{ $payout->partner->payout_details }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-gray-400">Statement</div>
            <div>Period: {{ $payout->periodLabel() }}</div>
            <div>Due: {{ $payout->due_date->toDateString() }}</div>
            <div>Status: <x-affiliate-badge :status="$payout->status" /></div>
            <div class="mt-2 text-xl font-bold">{{ $m($payout->amount) }} {{ $payout->currency }}</div>
            <div class="text-xs text-gray-400">Downloaded {{ $payout->download_count }}×</div>
        </x-card>
    </div>

    <x-card class="mb-4 !p-0 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                @foreach (['Period', 'Business', 'Kind', 'Base', 'Rate', 'Amount'] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($payout->items as $it)
                <tr>
                    <td class="px-4 py-3">{{ sprintf('%02d/%04d', $it->period_month, $it->period_year) }}</td>
                    <td class="px-4 py-3">{{ $it->business_name_snapshot }}</td>
                    <td class="px-4 py-3">{{ $it->kind }}</td>
                    <td class="px-4 py-3">{{ $it->kind === 'commission' ? $m($it->base_amount) : '—' }}</td>
                    <td class="px-4 py-3">{{ $it->kind === 'commission' ? rtrim(rtrim(number_format((float) $it->rate, 3), '0'), '.') . '%' : '—' }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $m($it->commission_amount) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-card>

    @if (! in_array($payout->status, ['paid', 'void']))
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <x-card>
                <form method="POST" action="{{ route('superadmin.affiliate.payouts.update', $payout) }}">
                    @csrf @method('PATCH')<input type="hidden" name="action" value="mark_paid">
                    <x-input-label value="Payment reference" />
                    <x-text-input name="reference" class="mt-1 block w-full" placeholder="e.g. PayPal txn / bank ref" />
                    <x-primary-button class="mt-2">Mark as paid</x-primary-button>
                </form>
            </x-card>
            <x-card>
                <form method="POST" action="{{ route('superadmin.affiliate.payouts.update', $payout) }}"
                      onsubmit="return confirm('Void this statement? Its lines go back to Approved.')">
                    @csrf @method('PATCH')<input type="hidden" name="action" value="void">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Voiding releases every line on this statement so it can be re-generated.</p>
                    <x-secondary-button class="mt-2">Void statement</x-secondary-button>
                </form>
            </x-card>
        </div>
    @elseif ($payout->status === 'paid')
        <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800">
            Paid {{ $payout->paid_at?->format('d/m/Y') }}{{ $payout->payment_reference ? ' · ref ' . $payout->payment_reference : '' }}
        </div>
    @endif
</x-superadmin-layout>
