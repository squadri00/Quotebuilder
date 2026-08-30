@php
    $m = fn($n) => '$' . number_format((float) $n, 2);
    $tabs = ['referrals' => 'Referrals', 'prospects' => 'Prospects', 'commissions' => 'Commissions', 'payouts' => 'Payouts'];
@endphp
<x-superadmin-layout :title="'Partner — ' . $partner->name">
    <x-slot name="header">
        <div class="flex w-full items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                {{ $partner->name }}
                <span class="ml-1 rounded bg-orange-100 px-2 py-0.5 font-mono text-xs font-semibold text-orange-700">{{ $partner->partner_code }}</span>
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.affiliate.partners.edit', $partner) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">Edit</a>
                <form method="POST" action="{{ route('superadmin.affiliate.partners.impersonate', $partner) }}"
                      onsubmit="return confirm('Open the partner portal as this partner?')">
                    @csrf
                    <button class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">Open portal as partner</button>
                </form>
            </div>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-5 grid grid-cols-1 gap-3 lg:grid-cols-3">
        <x-card>
            <div class="text-xs text-gray-400">Status</div>
            <div><x-affiliate-badge :status="$partner->status" /></div>
            <div class="mt-3 text-xs text-gray-400">Commission rate</div>
            <div class="text-lg font-bold">{{ rtrim(rtrim(number_format((float) $partner->commission_rate, 3), '0'), '.') }}%</div>
            <form method="POST" action="{{ route('superadmin.affiliate.partners.status', $partner) }}" class="mt-3 flex flex-wrap gap-2">
                @csrf @method('PATCH')
                @if ($partner->status !== 'active')<button name="status" value="active" class="rounded bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white">Activate</button>@endif
                @if ($partner->status !== 'suspended')<button name="status" value="suspended" class="rounded border border-gray-300 px-2.5 py-1 text-xs dark:border-gray-600">Suspend</button>@endif
                @if ($partner->status === 'pending')<button name="status" value="rejected" class="rounded border border-gray-300 px-2.5 py-1 text-xs dark:border-gray-600">Reject</button>@endif
            </form>
        </x-card>
        <x-card>
            <div class="text-xs text-gray-400">Referral link</div>
            <div class="mt-1 break-all rounded bg-gray-100 p-2 font-mono text-xs dark:bg-gray-700">{{ $partner->referralUrl() }}</div>
            <div class="mt-2 text-xs text-gray-400">
                {{ $stats['clicks'] }} clicks · {{ $stats['signups'] }} signups · {{ $stats['active_referrals'] }} active
            </div>
            <div class="text-xs text-gray-400">{{ $partner->email }}{{ $partner->phone ? ' · ' . $partner->phone : '' }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-gray-400">Commission ({{ $stats['currency'] }})</div>
            <table class="mt-1 w-full text-sm">
                <tr><td>This month</td><td class="text-right font-semibold text-gray-900 dark:text-gray-100">{{ $m($stats['commission_month']) }}</td></tr>
                <tr><td>Pending approval</td><td class="text-right">{{ $m($stats['commission_pending']) }}</td></tr>
                <tr><td>Approved, unpaid</td><td class="text-right">{{ $m($stats['commission_unpaid']) }}</td></tr>
                <tr><td>Paid to date</td><td class="text-right">{{ $m($stats['commission_paid']) }}</td></tr>
                <tr><td class="font-semibold text-gray-900 dark:text-gray-100">Lifetime</td><td class="text-right font-bold">{{ $m($stats['commission_lifetime']) }}</td></tr>
            </table>
        </x-card>
    </div>

    <div class="mb-4 flex gap-1 border-b border-gray-200 dark:border-gray-700">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('superadmin.affiliate.partners.show', ['partner' => $partner, 'tab' => $key]) }}"
               class="border-b-2 px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'referrals')
        <x-card class="mb-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Attach a business to this partner</h3>
            <form method="POST" action="{{ route('superadmin.affiliate.partners.attach', $partner) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label value="Business" />
                    <select name="business_id" required class="mt-1 rounded-md border-gray-300 text-sm dark:bg-gray-700 dark:text-gray-100">
                        <option value="">Select…</option>
                        @foreach ($attachable as $b)<option value="{{ $b->id }}">{{ $b->name }} (#{{ $b->id }})</option>@endforeach
                    </select>
                </div>
                <div><x-input-label value="Rate override %" /><x-text-input name="rate" type="number" step="0.001" class="mt-1 w-28" placeholder="default" /></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="approve" value="1" checked class="rounded border-gray-300"> Approve now</label>
                <x-primary-button>Attach</x-primary-button>
            </form>
        </x-card>
        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                    @foreach (['Business', 'Plan', 'Source', 'Rate', 'Status', 'First commission'] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($referrals as $r)
                        <tr>
                            <td class="px-4 py-3">{{ $r->business?->name }}<div class="text-xs text-gray-400">{{ $r->business?->is_active ? 'active' : 'inactive' }}</div></td>
                            <td class="px-4 py-3">{{ $r->business?->plan?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $r->source }}</td>
                            <td class="px-4 py-3">{{ $r->commission_rate !== null ? $r->commission_rate . '%' : 'default' }}</td>
                            <td class="px-4 py-3"><x-affiliate-badge :status="$r->status" /></td>
                            <td class="px-4 py-3 text-gray-400">{{ $r->first_commission_at?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No referrals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

    @elseif ($tab === 'prospects')
        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                    @foreach (['Company', 'Region', 'Status', 'Claimed', 'Locked until', 'Converted'] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($prospects as $p)
                        <tr>
                            <td class="px-4 py-3">{{ $p->company_name }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ trim(($p->city ?? '') . ' ' . ($p->state_province ?? '')) ?: '—' }}</td>
                            <td class="px-4 py-3"><x-affiliate-badge :status="$p->status" /></td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->claimed_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->claim_expires_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->converted_business_id ? '#' . $p->converted_business_id : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No prospects claimed.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

    @elseif ($tab === 'commissions')
        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                    @foreach (['Period', 'Business', 'Kind', 'Base', 'Rate', 'Amount', 'Status'] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($commissions as $c)
                        <tr>
                            <td class="px-4 py-3">{{ sprintf('%04d-%02d', $c->period_year, $c->period_month) }}</td>
                            <td class="px-4 py-3">{{ $c->business?->name ?? $c->business_name_snapshot }}</td>
                            <td class="px-4 py-3">{{ $c->kind }}</td>
                            <td class="px-4 py-3">{{ $m($c->base_amount) }}</td>
                            <td class="px-4 py-3">{{ rtrim(rtrim(number_format((float) $c->rate, 3), '0'), '.') }}%</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $m($c->commission_amount) }}</td>
                            <td class="px-4 py-3"><x-affiliate-badge :status="$c->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No commission recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
        <p class="mt-2 text-xs text-gray-400">Manage individual lines on <a href="{{ route('superadmin.affiliate.commissions.index') }}" class="text-indigo-600">Commissions</a>.</p>

    @else
        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50"><tr>
                    @foreach (['Number', 'Period', 'Lines', 'Amount', 'Status', 'Paid', ''] as $h)<th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($payouts as $p)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $p->payout_number }}</td>
                            <td class="px-4 py-3">{{ sprintf('%04d-%02d', $p->period_year, $p->period_month) }}</td>
                            <td class="px-4 py-3">{{ $p->commission_count }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $m($p->amount) }} {{ $p->currency }}</td>
                            <td class="px-4 py-3"><x-affiliate-badge :status="$p->status" /></td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->paid_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('superadmin.affiliate.payouts.show', $p) }}" class="text-indigo-600">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No statements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    @endif

    @if ($partner->notes)
        <x-card class="mt-4">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Internal notes</div>
            <div class="mt-1 whitespace-pre-wrap text-sm text-gray-500 dark:text-gray-400">{{ $partner->notes }}</div>
        </x-card>
    @endif
</x-superadmin-layout>
