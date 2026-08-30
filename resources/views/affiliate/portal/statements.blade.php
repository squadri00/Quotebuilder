@php
    $m = fn($n) => '$' . number_format((float) $n, 2);
    $previewTotal = $previewRows->sum('commission_amount');
@endphp
<x-affiliate-layout title="Statements" active="statements">

    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-1 text-sm font-semibold">Generate a statement</h2>
        <p class="mb-3 text-xs text-slate-500">
            Turn a month's approved commission into an invoice you send us. Minimum {{ $m($minPayout) }} {{ $currency }}.
            Once finalized a month is locked.
        </p>
        @if ($eligible->isEmpty())
            <p class="text-sm text-slate-400">No months are ready yet. Commission has to be approved first.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-500"><th class="py-1">Month</th><th>Lines</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                @foreach ($eligible as $mn)
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ \Carbon\Carbon::create($mn->period_year, $mn->period_month, 1)->format('F Y') }}</td>
                        <td>{{ $mn->line_count }}</td>
                        <td class="font-semibold">{{ $m($mn->amount_total) }}</td>
                        <td class="text-right">
                            <a href="{{ route('affiliate.portal.statements', ['y' => $mn->period_year, 'm' => $mn->period_month]) }}"
                               class="rounded border border-slate-300 px-3 py-1 text-xs hover:bg-slate-50">Preview</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($previewRows->isNotEmpty())
        <div class="mb-5 rounded-xl border border-orange-300 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold">Preview — {{ \Carbon\Carbon::create($previewYear, $previewMonth, 1)->format('F Y') }}</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-500"><th class="py-1">Business</th><th>Type</th><th>Base</th><th>Rate</th><th>Amount</th></tr></thead>
                <tbody>
                @foreach ($previewRows as $r)
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ $r->business?->name ?? $r->business_name_snapshot }}</td>
                        <td class="text-slate-500">{{ $r->kind }}</td>
                        <td>{{ $r->kind === 'commission' ? $m($r->base_amount) : '—' }}</td>
                        <td>{{ $r->kind === 'commission' ? rtrim(rtrim(number_format((float) $r->rate, 3), '0'), '.') . '%' : '—' }}</td>
                        <td class="font-semibold">{{ $m($r->commission_amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="mt-2 text-right text-base font-bold">Total: {{ $m($previewTotal) }} {{ $currency }}</div>
            <form method="POST" action="{{ route('affiliate.portal.statements.finalize') }}" class="mt-3"
                  onsubmit="return confirm('Finalize this statement? The month will be locked.')">
                @csrf
                <input type="hidden" name="y" value="{{ $previewYear }}">
                <input type="hidden" name="m" value="{{ $previewMonth }}">
                <button class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Finalize statement</button>
                <a href="{{ route('affiliate.portal.statements') }}" class="ml-2 text-sm text-slate-500 hover:underline">Cancel</a>
            </form>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Your statements</h2>
        @if ($payouts->isEmpty())
            <p class="text-sm text-slate-400">None yet.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-500"><th class="py-1">Number</th><th>Period</th><th>Amount</th><th>Status</th><th>Paid</th><th></th></tr></thead>
                <tbody>
                @foreach ($payouts as $p)
                    <tr class="border-t border-slate-100">
                        <td class="py-2 font-mono text-xs">{{ $p->payout_number }}</td>
                        <td>{{ \Carbon\Carbon::create($p->period_year, $p->period_month, 1)->format('M Y') }}</td>
                        <td class="font-semibold">{{ $m($p->amount) }} {{ $p->currency }}</td>
                        <td><x-affiliate-badge :status="$p->status" /></td>
                        <td class="text-slate-500">{{ $p->paid_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('affiliate.portal.statements.show', $p) }}"
                               class="rounded border border-slate-300 px-3 py-1 text-xs hover:bg-slate-50">Open</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-affiliate-layout>
