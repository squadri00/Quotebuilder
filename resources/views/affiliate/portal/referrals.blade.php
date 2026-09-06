@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<x-affiliate-layout title="My referrals" active="referrals">
    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-1 text-sm font-semibold">Businesses you referred ({{ $rows->count() }})</h2>
        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
            Referrals stay <em>pending</em> until we confirm them. Commission still accrues from day one and
            becomes payable the moment a referral is approved.
        </p>

        @if ($rows->isEmpty())
            <p class="text-sm text-slate-400 dark:text-slate-500">No referrals yet. Your link:
                <span class="font-mono text-xs">{{ $partner->referralUrl() }}</span></p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-slate-500 dark:text-slate-400">
                        <th class="py-2">Business</th><th>Plan</th><th>Account</th><th>Referral</th>
                        <th>Rate</th><th>Lifetime commission</th><th>Last payment</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($rows as $r)
                        <tr class="border-t border-slate-100 dark:border-slate-800/60">
                            <td class="py-2">
                                <div class="font-semibold">{{ $r->business?->name }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500">since {{ $r->business?->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $r->business?->plan?->name ?? '—' }}</td>
                            <td><x-affiliate-badge :status="$r->business?->is_active ? 'active' : 'suspended'" /></td>
                            <td><x-affiliate-badge :status="$r->status" /></td>
                            <td>{{ rtrim(rtrim(number_format($r->effectiveRate(), 3), '0'), '.') }}%</td>
                            <td class="font-semibold">{{ $m($r->lifetime_commission) }}</td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $r->last_payment ? \Carbon\Carbon::parse($r->last_payment)->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-affiliate-layout>
