<x-superadmin-layout title="Prospect Registry">
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Prospect Registry</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($overlap->isNotEmpty())
        <x-card class="mb-5 border-red-200">
            <h3 class="mb-3 text-sm font-semibold text-red-700">⚠ Overlap — same client worked by multiple partners (last 180 days)</h3>
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th class="py-1">Company</th><th>Partners</th><th>Claims</th><th>Latest expiry</th></tr></thead>
                <tbody>
                @foreach ($overlap as $o)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-2">{{ $o->company_name }}</td>
                        <td>{{ $o->partner_count }} partners</td>
                        <td>{{ $o->claim_count }}</td>
                        <td class="text-gray-400">{{ \Carbon\Carbon::parse($o->latest_expiry)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-card>
    @endif

    <x-card class="!p-0 overflow-hidden">
        <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-4 dark:border-gray-700">
            <input name="q" value="{{ $q }}" placeholder="Company, city, contact, email" class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
            <select name="status" class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                <option value="">All statuses</option>
                @foreach (['open', 'working', 'won', 'lost', 'expired', 'released'] as $s)<option value="{{ $s }}" @selected($statusFilter === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <select name="partner" class="rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                <option value="">All partners</option>
                @foreach ($partners as $pp)<option value="{{ $pp->id }}" @selected($partnerFilter === $pp->id)>{{ $pp->name }} ({{ $pp->partner_code }})</option>@endforeach
            </select>
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                    @foreach (['Company', 'Contact', 'Region', 'Partner', 'Status', 'Locked until', 'Actions'] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($rows as $r)
                    <tr>
                        <td class="px-4 py-3">{{ $r->company_name }}@if($r->website)<div class="text-xs text-gray-400">{{ $r->website }}</div>@endif</td>
                        <td class="px-4 py-3 text-gray-400">{{ $r->contact_name ?: '—' }}@if($r->phone)<br>{{ $r->phone }}@endif</td>
                        <td class="px-4 py-3 text-gray-400">{{ trim(($r->city ?? '') . ' ' . ($r->state_province ?? '')) ?: '—' }}</td>
                        <td class="px-4 py-3"><a href="{{ route('superadmin.affiliate.partners.show', $r->partner_id) }}" class="text-indigo-600">{{ $r->partner?->partner_code }}</a></td>
                        <td class="px-4 py-3"><x-affiliate-badge :status="$r->status" />@if($r->converted_business_id)<div class="text-xs text-gray-400">→ #{{ $r->converted_business_id }}</div>@endif</td>
                        <td class="px-4 py-3 text-gray-400">{{ $r->claim_expires_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if (in_array($r->status, ['open', 'working']))
                                <div class="flex flex-wrap gap-1">
                                    <form method="POST" action="{{ route('superadmin.affiliate.prospects.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="extend"><button class="rounded border border-gray-300 px-2 py-0.5 text-xs dark:border-gray-600">Extend</button></form>
                                    <form method="POST" action="{{ route('superadmin.affiliate.prospects.update', $r) }}" onsubmit="return confirm('Release this claim?')">@csrf @method('PATCH')<input type="hidden" name="action" value="release"><button class="rounded border border-gray-300 px-2 py-0.5 text-xs dark:border-gray-600">Release</button></form>
                                    <form method="POST" action="{{ route('superadmin.affiliate.prospects.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="reassign">
                                        <select name="partner_id" onchange="if(this.value)this.form.submit()" class="rounded border-gray-300 py-0 text-xs dark:bg-gray-700">
                                            <option value="">Reassign…</option>
                                            @foreach ($partners as $pp)@if($pp->id !== $r->partner_id)<option value="{{ $pp->id }}">{{ $pp->partner_code }}</option>@endif @endforeach
                                        </select>
                                    </form>
                                </div>
                            @else <span class="text-gray-400">—</span> @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No claims match.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $rows->links() }}</div>
    </x-card>
</x-superadmin-layout>
