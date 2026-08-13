<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Billing</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <x-card class="mb-4 border-red-200 bg-red-50">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </x-card>
    @endif

    @if (request('checkout') === 'success')
        <x-card class="mb-4 border-green-200 bg-green-50">
            <p class="text-sm font-medium text-green-800">Payment received — Stripe is finalizing your subscription. This page will show your new plan within a few seconds once the confirmation comes through.</p>
        </x-card>
    @elseif (request('checkout') === 'cancelled')
        <x-card class="mb-4 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <p class="text-sm text-gray-600 dark:text-gray-400">Checkout was cancelled — no charge was made.</p>
        </x-card>
    @endif

    @if (request('support_checkout') === 'success')
        <x-card class="mb-4 border-green-200 bg-green-50">
            <p class="text-sm font-medium text-green-800">Payment received — Stripe is finalizing Priority Support. This page will show it active within a few seconds once the confirmation comes through.</p>
        </x-card>
    @elseif (request('support_checkout') === 'cancelled')
        <x-card class="mb-4 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <p class="text-sm text-gray-600 dark:text-gray-400">Checkout was cancelled — no charge was made.</p>
        </x-card>
    @endif

    <x-card>
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Current Plan</p>
        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $business->plan?->name ?? 'No plan assigned' }}</p>

        @if ($subscription)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Stripe subscription status:
                <span class="font-medium">{{ $subscription->stripe_status }}</span>
                @if ($subscription->onGracePeriod())
                    &middot; cancels {{ $subscription->ends_at->format('M j, Y') }}
                @elseif ($renewalDate)
                    &middot; renews {{ $renewalDate->format('M j, Y') }}
                @endif
            </p>

            <div class="mt-4 flex items-center gap-3">
                @if ($business->hasStripeId())
                    <form method="POST" action="{{ route('billing.portal') }}">
                        @csrf
                        <x-secondary-button type="submit">Manage Billing</x-secondary-button>
                    </form>
                @endif

                @if ($subscription->onGracePeriod())
                    <form method="POST" action="{{ route('billing.resume') }}">
                        @csrf
                        <x-secondary-button type="submit">Resume Subscription</x-secondary-button>
                    </form>
                @elseif ($subscription->active())
                    <form method="POST" action="{{ route('billing.cancel') }}"
                        onsubmit="return confirm('Cancel your subscription? You will keep access until the end of the current billing period.');">
                        @csrf
                        <x-danger-button type="submit">Cancel Subscription</x-danger-button>
                    </form>
                @endif
            </div>
        @else
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if ($business->plan)
                    This plan was assigned directly by the platform (not billed through Stripe).
                @else
                    Not subscribed to a paid plan.
                @endif
            </p>

            @if ($business->hasStripeId())
                <form method="POST" action="{{ route('billing.portal') }}" class="mt-4">
                    @csrf
                    <x-secondary-button type="submit">Manage Billing</x-secondary-button>
                </form>
            @endif
        @endif

        @if ($platformTaxSplit && $platformTaxSplit['rate'] > 0)
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                Your {{ $business->plan->name }} price of ${{ number_format($business->plan->price, 2) }} includes {{ $platformTaxSplit['label'] }} ({{ rtrim(rtrim(number_format($platformTaxSplit['rate'], 3), '0'), '.') }}%) based on your business address —
                ${{ number_format($platformTaxSplit['base'], 2) }} + ${{ number_format($platformTaxSplit['tax'], 2) }} tax.
            </p>
        @endif
    </x-card>

    <x-card class="mt-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Add-on</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $supportAddon->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Direct access to submit and track support tickets. Independent of your plan — subscribe or cancel any time.
                </p>

                @if ($business->support_access_granted)
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Granted directly by the platform — not billed through Stripe.
                    </p>
                @elseif ($supportSubscription)
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Status: <span class="font-medium">{{ $supportSubscription->stripe_status }}</span>
                        @if ($supportSubscription->onGracePeriod())
                            &middot; cancels {{ $supportSubscription->ends_at->format('M j, Y') }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="shrink-0 text-right">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">${{ number_format($supportAddon->price, 2) }}<span class="text-sm font-normal text-gray-500 dark:text-gray-400">/mo</span></p>

                <div class="mt-3">
                    @if ($business->support_access_granted)
                        <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Active</span>
                    @elseif ($supportSubscription?->onGracePeriod())
                        <form method="POST" action="{{ route('billing.support.resume') }}">
                            @csrf
                            <x-secondary-button type="submit">Resume</x-secondary-button>
                        </form>
                    @elseif ($supportSubscription?->active())
                        <form method="POST" action="{{ route('billing.support.cancel') }}"
                            onsubmit="return confirm('Cancel Priority Support? You will keep access until the end of the current billing period.');">
                            @csrf
                            <x-danger-button type="submit">Cancel</x-danger-button>
                        </form>
                    @elseif ($supportAddon->isPurchasable())
                        <form method="POST" action="{{ route('billing.support.subscribe') }}">
                            @csrf
                            <x-primary-button type="submit">Add Priority Support</x-primary-button>
                        </form>
                    @else
                        <p class="text-xs text-gray-400 dark:text-gray-500">Not currently available.</p>
                    @endif
                </div>
            </div>
        </div>
    </x-card>

    @if (request('implementation_checkout') === 'success')
        <x-card class="mt-6 border-green-200 bg-green-50">
            <p class="text-sm font-medium text-green-800">Payment received — your Implementation Service order is now in our queue. We'll be in touch to get started.</p>
        </x-card>
    @elseif (request('implementation_checkout') === 'cancelled')
        <x-card class="mt-6 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <p class="text-sm text-gray-600 dark:text-gray-400">Checkout was cancelled — no charge was made.</p>
        </x-card>
    @endif

    <x-card class="mt-6">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Add-on</p>
        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Quote Builder Implementation Service</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Our team builds out your products/calculators for you. Pick a package and check out — a one-time charge, not a subscription.
        </p>

        @if ($implementationTiers->isEmpty())
            <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">Not currently available.</p>
        @else
            <div class="mt-4 flex items-end gap-3 flex-wrap" x-data="{ tierId: {{ $implementationTiers->first()->id }} }">
                <div>
                    <x-input-label for="implementation_tier" value="Package" />
                    <select id="implementation_tier" x-model.number="tierId"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        @foreach ($implementationTiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name }} — {{ $tier->product_count }} products — ${{ number_format($tier->price, 0) }}</option>
                        @endforeach
                    </select>
                </div>

                <form method="POST" :action="'{{ url('/billing/implementation') }}/' + tierId">
                    @csrf
                    <x-primary-button type="submit">Purchase</x-primary-button>
                </form>
            </div>
        @endif

        @if ($implementationOrders->isNotEmpty())
            <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Your Orders</p>
                <div class="space-y-2">
                    @foreach ($implementationOrders as $order)
                        @php
                            $statusColor = match ($order->status) {
                                'awaiting_payment' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                'paid' => 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                'in_progress' => 'bg-amber-50 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
                                'completed' => 'bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-300',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $order->tier_name }} ({{ $order->product_count }} products) — {{ $order->created_at->format('M j, Y') }}
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    ${{ number_format($order->price, 2) }}
                                    @if ($order->tax_amount > 0)
                                        + ${{ number_format($order->tax_amount, 2) }} {{ $order->tax_label }} = ${{ number_format($order->totalCharged(), 2) }}
                                    @endif
                                </span>
                            </span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $order->statusLabel() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-card>

    <div class="mt-6" x-data="{
            interval: 'monthly',
            subscribeUrlBase: '{{ url('/billing/subscribe') }}',
            swapUrlBase: '{{ url('/billing/swap') }}',
        }">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $subscription?->active() ? 'Change Plan' : 'Available Plans' }}</h3>

            <div class="inline-flex items-center gap-4 rounded-full border-2 border-indigo-200 dark:border-indigo-800 bg-white dark:bg-gray-800 px-4 py-2 shadow-sm" role="radiogroup" aria-label="Billing interval">
                <button type="button" @click="interval = 'monthly'"
                    role="radio" :aria-checked="interval === 'monthly'"
                    class="flex items-center gap-1.5 text-sm font-semibold transition"
                    :class="interval === 'monthly' ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-600'">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition"
                        :class="interval === 'monthly' ? 'border-indigo-600' : 'border-gray-300'">
                        <span class="h-2 w-2 rounded-full bg-indigo-600" x-show="interval === 'monthly'"></span>
                    </span>
                    Monthly
                </button>

                <button type="button" @click="interval = 'yearly'"
                    role="radio" :aria-checked="interval === 'yearly'"
                    class="flex items-center gap-1.5 text-sm font-semibold transition"
                    :class="interval === 'yearly' ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-600'">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition"
                        :class="interval === 'yearly' ? 'border-indigo-600' : 'border-gray-300'">
                        <span class="h-2 w-2 rounded-full bg-indigo-600" x-show="interval === 'yearly'"></span>
                    </span>
                    Yearly
                </button>

                <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-bold text-green-700 dark:text-green-300">
                    Save 2 months
                </span>
            </div>
        </div>

        @if ($tiers->isEmpty())
            <x-card class="text-center text-gray-500 dark:text-gray-400">
                No plans are available for self-service subscription yet.
            </x-card>
        @else
            <div class="space-y-4">
                @foreach ($tiers as $tier)
                    @php
                        $monthly = $tier['monthly'];
                        $yearly = $tier['yearly'];
                        $anyVariant = $monthly ?? $yearly;
                        $toVariantJson = fn ($variant) => $variant ? [
                            'id' => $variant->id,
                            'price' => (float) $variant->price,
                            'products' => $variant->max_products === null ? 'unlimited' : $variant->max_products,
                            'quotes' => $variant->max_quotes_per_month === null ? 'unlimited' : $variant->max_quotes_per_month,
                            'hasStripe' => (bool) $variant->stripe_price_id,
                            'isCurrent' => $business->plan_id === $variant->id,
                        ] : null;
                    @endphp
                    <div x-data="{
                            monthly: @js($toVariantJson($monthly)),
                            yearly: @js($toVariantJson($yearly)),
                            get plan() { return interval === 'yearly' && this.yearly ? this.yearly : this.monthly; },
                        }">
                        <x-card class="flex items-center justify-between flex-wrap gap-3">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $anyVariant->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    <span x-text="'$' + Math.round(plan.price)"></span><span x-text="interval === 'yearly' ? '/yr' : '/mo'"></span>
                                    &middot; <span x-text="plan.products"></span> products
                                    &middot; <span x-text="plan.quotes"></span> quotes/mo
                                </p>
                            </div>

                            <template x-if="plan.isCurrent">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Current plan</span>
                            </template>

                            <template x-if="!plan.isCurrent && !plan.hasStripe">
                                <span class="text-xs text-gray-400 dark:text-gray-500 text-right">Not available yet<br>— contact us to upgrade</span>
                            </template>

                            <template x-if="!plan.isCurrent && plan.hasStripe && {{ $subscription && $subscription->active() ? 'true' : 'false' }}">
                                <form method="POST" :action="swapUrlBase + '/' + plan.id">
                                    @csrf
                                    <x-secondary-button type="submit">Switch to this plan</x-secondary-button>
                                </form>
                            </template>

                            <template x-if="!plan.isCurrent && plan.hasStripe && !({{ $subscription && $subscription->active() ? 'true' : 'false' }})">
                                <form method="POST" :action="subscribeUrlBase + '/' + plan.id">
                                    @csrf
                                    <x-primary-button type="submit">Subscribe</x-primary-button>
                                </form>
                            </template>
                        </x-card>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Invoice History</h3>
        <x-card class="p-0 overflow-hidden">
            @if ($invoices->isEmpty())
                <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">No invoices yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $invoice->date()->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $invoice->total() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" rel="noopener" class="text-sm font-medium brand-text">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
