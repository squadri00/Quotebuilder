<?php

namespace App\Http\Controllers;

use App\Models\ImplementationOrder;
use App\Models\ImplementationTier;
use App\Models\Plan;
use App\Models\SupportAddon;
use App\Services\PlatformTaxCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;

class BillingController extends Controller
{
    public function index(): View
    {
        $business = Auth::user()->business;

        $tiers = Plan::groupedActiveTiers();

        $subscription = $business->subscription('default');

        // Only fetched for an active, non-cancelling subscription — Cashier
        // doesn't store the renewal date locally, so this is a live Stripe
        // API call, not something to do on every page load in a hot path.
        // current_period_end moved from the subscription root to the first
        // subscription item as of Stripe's 2025+ API versions.
        // Both of these are live Stripe API calls, so a business whose
        // stripe_id is stale (e.g. points at a customer in a different
        // Stripe account than the one currently configured) must not take
        // the whole page down — degrade to "unknown" instead of a 500.
        $renewalDate = null;
        if ($subscription && $subscription->active() && ! $subscription->onGracePeriod()) {
            try {
                $periodEnd = $subscription->asStripeSubscription()->items->first()?->current_period_end;
                $renewalDate = $periodEnd ? \Carbon\Carbon::createFromTimestamp($periodEnd) : null;
            } catch (ApiErrorException $e) {
                Log::warning('Could not fetch renewal date from Stripe.', ['business_id' => $business->id, 'error' => $e->getMessage()]);
            }
        }

        $invoices = collect();
        if ($business->hasStripeId()) {
            try {
                $invoices = $business->invoices();
            } catch (ApiErrorException $e) {
                Log::warning('Could not fetch invoices from Stripe.', ['business_id' => $business->id, 'error' => $e->getMessage()]);
            }
        }

        $supportAddon = SupportAddon::get();
        $supportSubscription = $business->subscription('support');

        $implementationTiers = ImplementationTier::where('is_active', true)->orderBy('price')->get();
        $implementationOrders = $business->implementationOrders()->latest()->get();

        // Real, forward-looking breakdown per tier — shown before the
        // business ever clicks Purchase, so the tax-inclusive total isn't
        // a surprise they only discover on Stripe's own payment page. This
        // is the exact same addToBase() call purchaseImplementation() uses
        // to build the real charge, so it can never drift from what
        // actually gets billed.
        $taxCalculator = new PlatformTaxCalculator;
        $implementationTierTax = $implementationTiers->mapWithKeys(
            fn (ImplementationTier $tier) => [$tier->id => $taxCalculator->addToBase($business, (float) $tier->price)]
        );

        // Display-only — informational for the business, never changes what
        // Stripe actually charges (that's always the plan's one fixed
        // price). Only computed when there's a real plan price to split.
        $platformTaxSplit = $business->plan
            ? (new PlatformTaxCalculator)->splitFromTotal($business, (float) $business->plan->price)
            : null;

        return view('billing.index', compact(
            'business', 'tiers', 'subscription', 'renewalDate', 'invoices', 'supportAddon', 'supportSubscription',
            'implementationTiers', 'implementationOrders', 'implementationTierTax', 'platformTaxSplit'
        ));
    }

    /**
     * Redirects to Stripe Checkout for a brand new subscription. The
     * business's plan_id is NOT set here — the webhook is the single
     * source of truth for that (see StripeWebhookController), so it stays
     * consistent whether the subscription started here, changed in the
     * Stripe customer portal, or was modified directly in the Stripe
     * Dashboard.
     */
    public function subscribe(Request $request, Plan $plan): RedirectResponse|Checkout
    {
        abort_unless($plan->stripe_price_id, 404, 'This plan is not set up for Stripe billing.');

        $business = Auth::user()->business;

        if ($business->subscribed('default')) {
            return back()->with('error', 'This business already has an active subscription. Cancel it first to switch plans this way, or use Change Plan below.');
        }

        return $business->newSubscription('default', $plan->stripe_price_id)->checkout([
            'success_url' => route('billing.index').'?checkout=success',
            'cancel_url' => route('billing.index').'?checkout=cancelled',
        ]);
    }

    /**
     * Upgrade/downgrade an existing subscription to a different plan's
     * price. Like subscribe(), plan_id itself is only ever updated by the
     * webhook once Stripe confirms the change.
     */
    public function swap(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->stripe_price_id, 404, 'This plan is not set up for Stripe billing.');

        $business = Auth::user()->business;

        abort_unless($business->subscribed('default'), 404, 'No active subscription to change.');

        try {
            $business->subscription('default')->swap($plan->stripe_price_id);
        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [$exception->payment->id, 'redirect' => route('billing.index')]);
        }

        return redirect()->route('billing.index')->with('status', "Plan change to \"{$plan->name}\" submitted.");
    }

    /**
     * Stripe's own hosted Customer Portal — plan switching, cancellation,
     * and payment method updates all happen there instead of custom UI
     * here. Requires a Customer Portal configuration to be set up once in
     * the Stripe Dashboard (Settings > Billing > Customer portal) or this
     * throws a Stripe API error; see Super Admin > Platform Settings help
     * text / the setup notes given alongside this feature.
     */
    public function billingPortal(): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasStripeId(), 404, 'No billing account yet — subscribe to a plan first.');

        return $business->redirectToBillingPortal(route('billing.index'));
    }

    public function cancel(): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->subscribed('default'), 404, 'No active subscription to cancel.');

        $business->subscription('default')->cancel();

        return redirect()->route('billing.index')->with('status', 'Your subscription will end at the close of the current billing period.');
    }

    public function resume(): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->subscription('default')?->onGracePeriod(), 404, 'No cancelled-but-still-active subscription to resume.');

        $business->subscription('default')->resume();

        return redirect()->route('billing.index')->with('status', 'Subscription resumed.');
    }

    /**
     * Priority Support is a standalone subscription — independent of
     * which Plan the business is on, and independent of a Plan even
     * being active at all. It's Cashier's 'support' named subscription
     * on the same Stripe customer, separate from 'default'.
     */
    public function subscribeSupport(): RedirectResponse|Checkout
    {
        $supportAddon = SupportAddon::get();

        abort_unless($supportAddon->isPurchasable(), 404, 'Priority Support is not currently available.');

        $business = Auth::user()->business;

        if ($business->subscribed('support')) {
            return back()->with('error', 'Priority Support is already active on this account.');
        }

        return $business->newSubscription('support', $supportAddon->stripe_price_id)->checkout([
            'success_url' => route('billing.index').'?support_checkout=success',
            'cancel_url' => route('billing.index').'?support_checkout=cancelled',
        ]);
    }

    public function cancelSupport(): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->subscribed('support'), 404, 'No active Priority Support subscription to cancel.');

        $business->subscription('support')->cancel();

        return redirect()->route('billing.index')->with('status', 'Priority Support will end at the close of the current billing period.');
    }

    public function resumeSupport(): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->subscription('support')?->onGracePeriod(), 404, 'No cancelled-but-still-active Priority Support subscription to resume.');

        $business->subscription('support')->resume();

        return redirect()->route('billing.index')->with('status', 'Priority Support resumed.');
    }

    /**
     * The Implementation Service is a one-time paid service (someone on
     * the platform team manually builds the purchased number of products
     * for the business) — a one-off Stripe Checkout charge, not a
     * subscription. The order is created up front as 'awaiting_payment'
     * so it's trackable even before Stripe confirms payment; the webhook
     * (see Stripe\WebhookController::handleCheckoutSessionCompleted) is
     * what actually marks it 'paid', matched back to this order via the
     * checkout session's metadata.
     *
     * Unlike subscriptions, this is a one-off charge built fresh at
     * checkout time (Stripe `price_data`, not a persisted Price object),
     * so platform tax is genuinely added to what's charged here — an
     * Ontario business really pays more than one elsewhere. The tier's own
     * stripe_price_id still gates whether it's purchasable at all (a
     * Super-Admin "ready to sell" flag), but its stored amount is never
     * what actually gets charged; the tier's price plus tax is.
     */
    public function purchaseImplementation(ImplementationTier $tier): RedirectResponse|Checkout
    {
        abort_unless($tier->isPurchasable(), 404, 'This implementation package is not currently available.');

        $business = Auth::user()->business;

        $tax = (new PlatformTaxCalculator)->addToBase($business, (float) $tier->price);

        $order = ImplementationOrder::create([
            'business_id' => $business->id,
            'implementation_tier_id' => $tier->id,
            'tier_name' => $tier->name,
            'product_count' => $tier->product_count,
            'price' => $tax['base'],
            'tax_label' => $tax['label'],
            'tax_rate' => $tax['rate'],
            'tax_amount' => $tax['tax'],
            'status' => 'awaiting_payment',
        ]);

        $checkout = $business->checkout([[
            'price_data' => [
                'currency' => $business->preferredCurrency(),
                'product_data' => [
                    'name' => $tier->name.' — Quote Builder Implementation Service',
                ],
                'unit_amount' => (int) round($tax['total'] * 100),
            ],
            'quantity' => 1,
        ]], [
            'success_url' => route('billing.index').'?implementation_checkout=success',
            'cancel_url' => route('billing.index').'?implementation_checkout=cancelled',
            'metadata' => ['implementation_order_id' => $order->id],
        ]);

        // Stamped on immediately (not just on the webhook's payment
        // confirmation) so a stuck 'awaiting_payment' order can later be
        // reconciled against Stripe's own record of what happened to
        // this specific checkout link — see ExpireAbandonedImplementationOrders.
        $order->update(['stripe_checkout_session_id' => $checkout->id]);

        return $checkout;
    }
}
