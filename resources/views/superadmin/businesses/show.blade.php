<x-superadmin-layout :title="$business->name">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <a href="{{ route('superadmin.businesses.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">&larr; Businesses</a>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $business->name }}</h2>
            </div>

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('superadmin.businesses.impersonate', $business) }}">
                    @csrf
                    <x-secondary-button type="submit">Impersonate</x-secondary-button>
                </form>

                <form method="POST" action="{{ route('superadmin.businesses.toggle-active', $business) }}">
                    @csrf
                    @method('PATCH')
                    <x-secondary-button type="submit">
                        {{ $business->is_active ? 'Deactivate' : 'Activate' }}
                    </x-secondary-button>
                </form>

                @php
                    $hasActiveSubscription = ($subscription && $subscription->active()) || ($supportSubscription && $supportSubscription->active());
                @endphp
                <form method="POST" action="{{ route('superadmin.businesses.destroy', $business) }}"
                    onsubmit="return confirm('Permanently delete &quot;{{ $business->name }}&quot;? This deletes all of its products, questions, rules, quotes, and users. This cannot be undone.{{ $hasActiveSubscription ? ' It also has an active Stripe subscription — that will be cancelled immediately as part of this.' : '' }}');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">Delete</x-danger-button>
                </form>
            </div>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</p>
            <p class="mt-1">
                @if ($business->is_active)
                    <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Deactivated</span>
                @endif
            </p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Industry</p>
            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $business->industry?->name ?? '—' }}</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Signed up</p>
            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $business->created_at->format('M j, Y') }}</p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                Template{{ $business->templatesUsed->count() > 1 ? 's' : '' }} used
            </p>
            <p class="mt-1 text-gray-900 dark:text-gray-100">
                @if ($business->templatesUsed->isNotEmpty())
                    {{ $business->templatesUsed->pluck('name')->implode(', ') }}
                @else
                    None (started from scratch)
                @endif
            </p>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Products / Rules</p>
            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $business->products_count }} product(s) &middot; {{ $business->rules_count }} rule(s)</p>
            <a href="{{ route('superadmin.products.index', $business) }}" class="mt-2 inline-block text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Quote Builder &rarr;</a>
        </x-card>

        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Quotes generated</p>
            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $business->quotes_count }}</p>
        </x-card>
    </div>

    <div class="mt-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Copy Template Data</h3>
        <x-card class="opacity-60">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">Temporarily disabled</span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Disabled for now — running this a second time re-adds a template's entire product list with no duplicate check, so a business that already has products from that template would end up with two of everything. It'll come back once that's fixed, or be removed for good if the business's own Templates page (which checks for duplicates, one product at a time) turns out to cover the same need safely.
            </p>
        </x-card>
    </div>

    <div class="mt-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Plan</h3>
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stripe Subscription Status</p>
            <p class="mt-1">
                @if ($subscription)
                    @php
                        $statusColor = match(true) {
                            $subscription->active() && ! $subscription->onGracePeriod() => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                            $subscription->onGracePeriod() => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                            $subscription->pastDue() => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                            default => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $subscription->stripe_status }}</span>
                    @if ($subscription->onGracePeriod())
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">cancels {{ $subscription->ends_at->format('M j, Y') }}</span>
                    @endif
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">No Stripe subscription</span>
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This reflects Stripe's own records — it's separate from the plan set below, which the platform can override directly (e.g. for comped or trial accounts) regardless of what Stripe says.</p>

            <form method="POST" action="{{ route('superadmin.businesses.assign-plan', $business) }}" class="mt-4 flex items-end gap-3">
                @csrf
                @method('PATCH')

                <div class="flex-1 max-w-xs">
                    <x-input-label for="plan_id" value="Assigned Plan (manual override)" />
                    <select id="plan_id" name="plan_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
                        <option value="">No plan</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($business->plan_id === $plan->id)>
                                {{ $plan->name }} (${{ number_format($plan->price, 2) }}/{{ $plan->billing_interval }}){{ $plan->is_active ? '' : ' — deactivated' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-secondary-button type="submit">Save</x-secondary-button>
            </form>

            @if ($business->plan)
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Includes: {{ $business->plan->features()->pluck('name')->implode(', ') ?: 'no features' }}
                </p>
            @endif
        </x-card>
    </div>

    <div class="mt-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Priority Support</h3>
        <x-card>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stripe Subscription Status</p>
            <p class="mt-1">
                @if ($supportSubscription)
                    @php
                        $supportStatusColor = match(true) {
                            $supportSubscription->active() && ! $supportSubscription->onGracePeriod() => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                            $supportSubscription->onGracePeriod() => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                            $supportSubscription->pastDue() => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                            default => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $supportStatusColor }}">{{ $supportSubscription->stripe_status }}</span>
                    @if ($supportSubscription->onGracePeriod())
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">cancels {{ $supportSubscription->ends_at->format('M j, Y') }}</span>
                    @endif
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">No Stripe subscription</span>
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This reflects Stripe's own records. The toggle below grants or revokes access directly — for comped accounts, VIPs, or anyone paying outside Stripe — regardless of what Stripe says.</p>

            <form method="POST" action="{{ route('superadmin.businesses.toggle-support-access', $business) }}" class="mt-4">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-3">
                    @if ($business->support_access_granted)
                        <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Granted (manual override)</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Not granted</span>
                    @endif

                    <x-secondary-button type="submit">
                        {{ $business->support_access_granted ? 'Revoke Access' : 'Grant Access' }}
                    </x-secondary-button>
                </div>
            </form>

            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                Overall access: <span class="font-medium {{ $business->hasSupportAccess() ? 'text-green-700 dark:text-green-300' : 'text-gray-500' }}">{{ $business->hasSupportAccess() ? 'Has Priority Support' : 'No Priority Support' }}</span>
                ({{ $business->support_access_granted ? 'manual grant' : ($supportSubscription?->active() ? 'via Stripe' : 'none') }})
            </p>
        </x-card>
    </div>

    <div class="mt-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Users ({{ $business->users_count }})</h3>
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($business->users as $user)
                            <tr>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    @if ($user->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Deactivated</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-superadmin-layout>
