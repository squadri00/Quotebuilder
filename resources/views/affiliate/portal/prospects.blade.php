@php
    $edit = $editing ? $mine->firstWhere('id', $editing) : null;
    $v = fn($k, $d = '') => old($k, $edit->$k ?? $d);
@endphp
<x-affiliate-layout title="Prospects & market" active="prospects">

    @if ($conflict)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
            <strong>Already claimed.</strong> Another partner is working
            “{{ \Illuminate\Support\Str::substr($conflict->company_name, 0, 2) }}••••{{ \Illuminate\Support\Str::substr($conflict->company_name, -1) }}”.
            It opens up on <strong>{{ \Carbon\Carbon::parse($conflict->claim_expires_at)->format('M j, Y') }}</strong>.
        </div>
    @endif

    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-1 text-sm font-semibold">{{ $edit ? 'Edit prospect' : 'Claim a new prospect' }}</h2>
        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
            Claiming locks this business to you for {{ $claimDays }} days of inactivity. Other partners see it's
            taken but not who has it or how to contact it.
        </p>
        <form method="POST"
              action="{{ $edit ? route('affiliate.portal.prospects.update', $edit) : route('affiliate.portal.prospects.store') }}">
            @csrf
            @if ($edit) @method('PATCH') @endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach ([
                    'company_name' => 'Company name *', 'contact_name' => 'Contact person',
                    'phone' => 'Phone', 'email' => 'Email', 'website' => 'Website', 'industry' => 'Industry',
                    'city' => 'City', 'state_province' => 'State / province',
                ] as $n => $l)
                    <div>
                        <label class="mb-1 block text-xs font-medium">{{ $l }}</label>
                        <input name="{{ $n }}" value="{{ $v($n) }}" @if($n === 'company_name') required @endif
                               class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                @endforeach
                <div>
                    <label class="mb-1 block text-xs font-medium">Country</label>
                    <select name="country" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                        <option value="">—</option>
                        @foreach ($countries as $c)<option value="{{ $c }}" @selected($v('country') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Likely plan (pipeline value)</label>
                    <select name="estimated_plan_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                        <option value="">—</option>
                        @foreach ($plans as $pl)<option value="{{ $pl->id }}" @selected((string) $v('estimated_plan_id') === (string) $pl->id)>{{ $pl->name }}</option>@endforeach
                    </select>
                </div>
                @if ($edit)
                    <div>
                        <label class="mb-1 block text-xs font-medium">Status</label>
                        <select name="status" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                            @foreach (['open', 'working', 'lost'] as $s)<option value="{{ $s }}" @selected($v('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
                        </select>
                    </div>
                @endif
            </div>
            <label class="mb-1 mt-3 block text-xs font-medium">Notes</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">{{ $v('notes') }}</textarea>
            <div class="mt-3">
                <button class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                    {{ $edit ? 'Save prospect' : 'Claim prospect' }}
                </button>
                @if ($edit)
                    <a href="{{ route('affiliate.portal.prospects') }}" class="ml-2 text-sm text-slate-500 dark:text-slate-400 hover:underline">Cancel</a>
                @endif
            </div>
        </form>
    </div>

    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 text-sm font-semibold">My prospects ({{ $mine->count() }})</h2>
        @if ($mine->isEmpty())
            <p class="text-sm text-slate-400 dark:text-slate-500">You haven't claimed any prospects yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-slate-500 dark:text-slate-400"><th class="py-2">Company</th><th>Region</th><th>Status</th><th>Locked until</th><th>Actions</th></tr></thead>
                    <tbody>
                    @foreach ($mine as $p)
                        @php $active = in_array($p->status, ['open', 'working']) && $p->claim_expires_at?->isFuture(); @endphp
                        <tr class="border-t border-slate-100 dark:border-slate-800/60">
                            <td class="py-2">
                                <div class="font-semibold">{{ $p->company_name }}</div>
                                @if ($p->contact_name)<div class="text-xs text-slate-400 dark:text-slate-500">{{ $p->contact_name }}</div>@endif
                            </td>
                            <td class="text-slate-500 dark:text-slate-400">{{ trim(($p->city ?? '') . ' ' . ($p->state_province ?? '')) ?: '—' }}</td>
                            <td><x-affiliate-badge :status="$p->status" /></td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $active ? $p->claim_expires_at->format('d/m/Y') : '—' }}</td>
                            <td class="whitespace-nowrap">
                                @if ($p->status !== 'won')
                                    <a href="{{ route('affiliate.portal.prospects', ['edit' => $p->id]) }}"
                                       class="rounded border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1 text-xs hover:bg-slate-50 dark:hover:bg-slate-700">Edit</a>
                                @endif
                                @if ($active)
                                    <form method="POST" action="{{ route('affiliate.portal.prospects.renew', $p) }}" class="inline">@csrf
                                        <button class="rounded border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1 text-xs hover:bg-slate-50 dark:hover:bg-slate-700">Extend</button>
                                    </form>
                                    <form method="POST" action="{{ route('affiliate.portal.prospects.release', $p) }}" class="inline"
                                          onsubmit="return confirm('Release this claim?')">@csrf
                                        <button class="rounded border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-2 py-1 text-xs hover:bg-slate-50 dark:hover:bg-slate-700">Release</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-1 text-sm font-semibold">Market board — who's working what</h2>
        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
            Every partner's active claims, anonymised. Check here before you prospect so you don't chase a
            business someone else already has.
        </p>
        <form method="GET" class="mb-3 flex gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Filter by city / region / industry"
                   class="max-w-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:border-orange-500 focus:ring-orange-500">
            <button class="rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-500 dark:text-slate-400"><th class="py-2">Company</th><th>Region</th><th>Industry</th><th>Stage</th><th>Locked until</th></tr></thead>
                <tbody>
                @forelse ($board as $b)
                    <tr class="border-t border-slate-100 dark:border-slate-800/60 {{ $b->is_mine ? 'bg-orange-50 dark:bg-orange-950/30' : '' }}">
                        <td class="py-2">{{ $b->masked_name }} @if($b->is_mine)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">you</span>@endif</td>
                        <td class="text-slate-500 dark:text-slate-400">{{ $b->region }}</td>
                        <td class="text-slate-500 dark:text-slate-400">{{ $b->industry ?? '—' }}</td>
                        <td><x-affiliate-badge :status="$b->status" /></td>
                        <td class="text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($b->claim_expires_at)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-center text-slate-400 dark:text-slate-500">No active claims right now — the field is open.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-affiliate-layout>
