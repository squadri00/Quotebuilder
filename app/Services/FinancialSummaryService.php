<?php

namespace App\Services;

use App\Models\ImplementationOrder;
use App\Models\Plan;
use App\Models\SupportAddon;
use App\Models\TaxCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

/**
 * Single source of truth for every money figure Super Admin sees — the
 * Financial Activity report and the home Dashboard's overview both read
 * from this rather than each keeping their own copy of the math, so the
 * two pages can never quietly drift apart. Recurring-revenue figures
 * (MRR, past-due) are computed live from local Cashier subscriptions;
 * collected-revenue figures (revenue, tax, breakdown, recent activity)
 * are computed from the TaxCollection ledger + paid ImplementationOrders
 * — no live Stripe API calls anywhere in this class, so every page
 * using it loads fast regardless of history size.
 */
class FinancialSummaryService
{
    private const ACTIVE_STATUSES = ['active', 'trialing'];

    /**
     * Sum of every currently active/trialing subscription's monthly-
     * equivalent price — yearly plans divided by 12. past_due is
     * deliberately excluded: that's a failed charge in a grace period,
     * not confirmed recurring revenue (see pastDue() for that figure).
     */
    public function mrr(): float
    {
        $plansByPrice = Plan::whereNotNull('stripe_price_id')->get()->keyBy('stripe_price_id');

        $planMrr = Subscription::where('type', 'default')
            ->whereIn('stripe_status', self::ACTIVE_STATUSES)
            ->get()
            ->sum(function (Subscription $sub) use ($plansByPrice) {
                $plan = $plansByPrice->get($sub->stripe_price);

                if (! $plan) {
                    return 0;
                }

                return $plan->billing_interval === 'yearly' ? $plan->price / 12 : $plan->price;
            });

        $supportAddon = SupportAddon::get();
        $activeSupportCount = $supportAddon->stripe_price_id
            ? Subscription::where('type', 'support')->whereIn('stripe_status', self::ACTIVE_STATUSES)
                ->where('stripe_price', $supportAddon->stripe_price_id)->count()
            : 0;

        return round($planMrr + ($activeSupportCount * (float) $supportAddon->price), 2);
    }

    /**
     * How many currently active/trialing subscriptions of each type —
     * the granular counts behind the MRR headline figure.
     */
    public function activeSubscriptionCounts(): array
    {
        return [
            'plan' => Subscription::where('type', 'default')->whereIn('stripe_status', self::ACTIVE_STATUSES)->count(),
            'support' => Subscription::where('type', 'support')->whereIn('stripe_status', self::ACTIVE_STATUSES)->count(),
        ];
    }

    /**
     * Subscriptions sitting in Stripe's past_due grace period right now —
     * a charge already failed once and access hasn't been revoked yet.
     * Surfaced separately from MRR because it's revenue at risk, not
     * revenue in hand: the platform owner needs to see this to know
     * something needs following up on, not just today's confirmed total.
     */
    public function pastDue(): array
    {
        $plansByPrice = Plan::whereNotNull('stripe_price_id')->get()->keyBy('stripe_price_id');
        $supportAddon = SupportAddon::get();

        $subs = Subscription::whereIn('type', ['default', 'support'])
            ->where('stripe_status', 'past_due')
            ->with('owner')
            ->get();

        $mrrAtRisk = $subs->sum(function (Subscription $sub) use ($plansByPrice, $supportAddon) {
            if ($sub->type === 'support') {
                return $sub->stripe_price === $supportAddon->stripe_price_id ? (float) $supportAddon->price : 0;
            }

            $plan = $plansByPrice->get($sub->stripe_price);

            return $plan ? ($plan->billing_interval === 'yearly' ? $plan->price / 12 : $plan->price) : 0;
        });

        return [
            'count' => $subs->count(),
            'mrr_at_risk' => round($mrrAtRisk, 2),
            'businesses' => $subs->pluck('owner.name')->filter()->unique()->values(),
        ];
    }

    public function revenue(?Carbon $since = null): float
    {
        return $this->taxCollectionSum('total_amount', $since) + $this->implementationSum($since);
    }

    public function tax(?Carbon $since = null): float
    {
        return $this->taxCollectionSum('amount', $since) + $this->implementationTaxSum($since);
    }

    /**
     * Revenue split across the three things businesses actually pay for,
     * optionally scoped to a start date (omit for all-time).
     */
    public function breakdown(?Carbon $since = null): array
    {
        return [
            'Plan Subscriptions' => $this->taxCollectionSum('total_amount', $since, 'default'),
            'Priority Support' => $this->taxCollectionSum('total_amount', $since, 'support'),
            'Implementation Service' => $this->implementationSum($since),
        ];
    }

    /**
     * The most recent revenue events across all three sources, newest
     * first — a quick "what just happened" glance rather than a ledger.
     */
    public function recentActivity(int $limit = 15): Collection
    {
        $subscriptionEntries = TaxCollection::with('business')->latest('collected_at')->limit($limit)->get()
            ->map(fn (TaxCollection $entry) => [
                'date' => $entry->collected_at,
                'business' => $entry->business?->name ?? 'Unknown',
                'label' => $entry->subscription_type === 'support' ? 'Priority Support' : 'Plan Subscription',
                'amount' => (float) $entry->total_amount,
            ]);

        $implementationEntries = ImplementationOrder::with('business')->where('status', 'paid')
            ->latest('paid_at')->limit($limit)->get()
            ->map(fn (ImplementationOrder $order) => [
                'date' => $order->paid_at,
                'business' => $order->business?->name ?? 'Unknown',
                'label' => 'Implementation — '.$order->tier_name,
                'amount' => $order->totalCharged(),
            ]);

        return $subscriptionEntries->concat($implementationEntries)
            ->sortByDesc(fn ($entry) => $entry['date'])
            ->take($limit)
            ->values();
    }

    private function taxCollectionSum(string $column, ?Carbon $since, ?string $subscriptionType = null): float
    {
        $query = TaxCollection::query();

        if ($since) {
            $query->where('collected_at', '>=', $since);
        }

        if ($subscriptionType) {
            $query->where('subscription_type', $subscriptionType);
        }

        return (float) $query->sum($column);
    }

    private function implementationSum(?Carbon $since = null): float
    {
        $query = ImplementationOrder::where('status', 'paid');

        if ($since) {
            $query->where('paid_at', '>=', $since);
        }

        return (float) $query->get()->sum->totalCharged();
    }

    private function implementationTaxSum(?Carbon $since = null): float
    {
        $query = ImplementationOrder::where('status', 'paid');

        if ($since) {
            $query->where('paid_at', '>=', $since);
        }

        return (float) $query->sum('tax_amount');
    }
}
