@php $m = fn($n) => '$' . number_format((float) $n, 2); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payout->payout_number }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none } }</style>
</head>
<body class="bg-white p-8 text-sm text-slate-800">
<div class="mx-auto max-w-3xl">
    <div class="no-print mb-6 flex gap-2">
        <a href="{{ route('affiliate.portal.statements') }}" class="rounded border border-slate-300 px-3 py-1.5">&larr; Back</a>
        <button onclick="window.print()" class="rounded bg-orange-600 px-3 py-1.5 font-semibold text-white">Print / Save PDF</button>
        <a href="{{ route('affiliate.portal.statements.txt', $payout) }}" class="rounded border border-slate-300 px-3 py-1.5">Download TXT</a>
        @if ($payout->status === 'finalized')
            <form method="POST" action="{{ route('affiliate.portal.statements.submit', $payout) }}" class="inline">@csrf
                <button class="rounded border border-slate-300 px-3 py-1.5">Mark as submitted</button>
            </form>
        @endif
    </div>

    <div class="flex flex-wrap justify-between gap-6">
        <div>
            <h1 class="text-xl font-bold">Commission statement</h1>
            <div class="text-slate-500">{{ $payout->payout_number }}</div>
            <div class="text-slate-500">Period: {{ $payout->periodLabel() }}</div>
            <div class="text-slate-500">Due: {{ $payout->due_date->toDateString() }} · Status: {{ ucfirst($payout->status) }}</div>
        </div>
        <div class="text-right">
            <div class="font-semibold">{{ $payout->company_name_snapshot }}</div>
            <div class="text-slate-500">{{ $payout->company_address_snapshot }}</div>
            <div class="text-slate-500">{{ $payout->company_email_snapshot }}</div>
        </div>
    </div>

    <div class="mt-5">
        <div class="text-slate-500">From</div>
        <div class="font-semibold">{{ $payout->partner_name_snapshot }} ({{ $payout->partner->partner_code }})</div>
        <div class="text-slate-500">
            {{ $payout->partner_address_snapshot }}{{ $payout->partner_citystate_snapshot ? ', ' . $payout->partner_citystate_snapshot : '' }}
            {{ $payout->partner_country_snapshot ? ', ' . $payout->partner_country_snapshot : '' }}<br>
            {{ $payout->partner_email_snapshot }}{{ $payout->partner_tax_id_snapshot ? ' · Tax ID ' . $payout->partner_tax_id_snapshot : '' }}
        </div>
    </div>

    <table class="mt-5 w-full border-collapse text-sm">
        <thead><tr class="bg-slate-50 text-left">
            <th class="border-b p-2">Period</th><th class="border-b p-2">Business</th><th class="border-b p-2">Type</th>
            <th class="border-b p-2">Base</th><th class="border-b p-2">Rate</th><th class="border-b p-2 text-right">Amount</th>
        </tr></thead>
        <tbody>
        @foreach ($payout->items as $it)
            <tr>
                <td class="border-b p-2">{{ sprintf('%02d/%04d', $it->period_month, $it->period_year) }}</td>
                <td class="border-b p-2">{{ $it->business_name_snapshot }}</td>
                <td class="border-b p-2">{{ $it->kind }}</td>
                <td class="border-b p-2">{{ $it->kind === 'commission' ? $m($it->base_amount) : '—' }}</td>
                <td class="border-b p-2">{{ $it->kind === 'commission' ? number_format((float) $it->rate, 2) . '%' : '—' }}</td>
                <td class="border-b p-2 text-right">{{ $m($it->commission_amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-4 text-right text-lg font-bold">Total due: {{ $m($payout->amount) }} {{ $payout->currency }}</div>
    <p class="mt-6 text-slate-500">
        Please email this statement to {{ $payout->company_email_snapshot }} for processing. Standard payment
        target is the 15th of the following month.
    </p>
</div>
</body>
</html>
