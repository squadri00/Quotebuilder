<?php

namespace App\Http\Controllers\Stripe;

use App\Models\Business;
use App\Models\ImplementationOrder;
use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Models\TaxCollection;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extends Cashier's own webhook handling (which keeps the local
 * `subscriptions` table in sync) to additionally keep businesses.plan_id
 * in sync — the single field the rest of the app (hasFeature(), etc.)
 * actually reads. Webhooks are the source of truth: nothing else in the
 * app sets plan_id as a side effect of a Stripe-driven subscription
 * change, only this controller does, based on what Stripe confirms
 * actually happened.
 */
class WebhookController extends CashierWebhookController
{
    /**
     * Cashier's own constructor only attaches signature verification
     * IF a webhook secret is configured — meaning an empty secret
     * (e.g. a fresh deploy where nobody filled in Super Admin >
     * Platform Settings yet) would silently accept unsigned,
     * unverified POSTs as if they were real Stripe events. Fail loudly
     * instead: refuse every webhook call until the secret is set,
     * rather than quietly trusting forged requests.
     */
    public function __construct()
    {
        abort_if(
            ! config('cashier.webhook.secret'),
            500,
            'Stripe webhook secret is not configured — set it in Super Admin > Platform Settings before webhooks can be processed.'
        );

        parent::__construct();
    }

    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        // Must happen before parent:: — Cashier's own handling can only
        // attach the subscription to a Business it can already find by
        // Stripe customer id, and for a brand new signup that Business
        // doesn't exist until this creates it.
        $this->finalizePendingRegistrationIfNeeded($payload);

        $response = parent::handleCustomerSubscriptionCreated($payload);

        $this->syncPlanFromSubscriptionPayload($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): ?Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncPlanFromSubscriptionPayload($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        // Businesses can have more than one named subscription now (the
        // main plan is 'default', Priority Support is 'support') — only
        // ever clear plan_id when it was actually the 'default'
        // subscription that ended. Otherwise cancelling support would
        // wrongly cancel the business's plan too.
        if (! $this->isDefaultSubscription($payload)) {
            return $response;
        }

        if ($business = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $business->update(['plan_id' => null]);

            Log::info('Stripe subscription canceled — cleared plan_id.', [
                'business_id' => $business->id,
                'stripe_subscription_id' => $payload['data']['object']['id'],
            ]);
        }

        return $response;
    }

    /**
     * A failed renewal charge already surfaces as a `customer.subscription
     * .updated` event with status=past_due, which the parent's handling
     * (plus syncPlanFromSubscriptionPayload above) already reacts to. This
     * override exists so Stripe gets a clean 200 instead of a "missing
     * method" fallback, and as the place to extend later if you want to
     * revoke access immediately on the *first* failed charge rather than
     * waiting for Stripe to mark the subscription past_due/canceled.
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        if ($business = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            Log::warning('Stripe invoice payment failed.', [
                'business_id' => $business->id,
                'stripe_invoice_id' => $payload['data']['object']['id'],
            ]);
        }

        return $this->successMethod();
    }

    /**
     * The local record-keeping side of Business::taxRates() — every
     * successfully paid subscription invoice gets logged here (base, tax,
     * total), regardless of whether tax actually applied, so the platform
     * owner has both a running HST/GST remittance total AND a real
     * revenue ledger for the Financial Activity report — neither has to
     * be reconstructed from raw Stripe data. Implementation Service
     * purchases never reach this: those are one-off Checkout charges, not
     * Invoices, so they're recorded separately on ImplementationOrder.
     * Guarded by stripe_invoice_id's unique constraint against a
     * redelivered webhook double-recording the same invoice.
     */
    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $response = parent::handleInvoicePaymentSucceeded($payload);

        $invoice = $payload['data']['object'];

        $business = $this->getUserByStripeId($invoice['customer'] ?? null);

        if (! $business instanceof Business) {
            return $response;
        }

        $taxTotal = collect($invoice['total_taxes'] ?? [])->sum('amount');
        $subscriptionId = $invoice['parent']['subscription_details']['subscription'] ?? null;
        $subscriptionType = $subscriptionId
            ? Subscription::where('stripe_id', $subscriptionId)->value('type')
            : null;

        $taxCollection = TaxCollection::firstOrCreate(
            ['stripe_invoice_id' => $invoice['id']],
            [
                'business_id' => $business->id,
                'subscription_type' => $subscriptionType,
                'amount' => $taxTotal / 100,
                'base_amount' => ($invoice['subtotal'] ?? 0) / 100,
                'total_amount' => ($invoice['total'] ?? 0) / 100,
                'currency' => strtoupper($invoice['currency'] ?? 'cad'),
                'collected_at' => now(),
            ]
        );

        // Referral-partner commission accrual for this payment.
        try {
            app(\App\Services\Affiliate\CommissionService::class)
                ->accrueForTaxCollection($taxCollection);
        } catch (\Throwable $e) {
            Log::warning('Affiliate accrual failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Estimate a commission clawback when a referred business is refunded.
     * Stripe's charge.refunded carries no per-invoice tax split, so the
     * clawback is base-of-refund × current partner rate — the super admin
     * can fine-tune the line on the commission ledger.
     */
    protected function handleChargeRefunded(array $payload): Response
    {
        $charge = $payload['data']['object'];
        $refunded = ($charge['amount_refunded'] ?? 0) / 100;
        $business = $this->getUserByStripeId($charge['customer'] ?? null);

        if ($business instanceof Business && $refunded > 0) {
            try {
                app(\App\Services\Affiliate\CommissionService::class)->clawbackForRefund(
                    $business->id,
                    $charge['id'] ?? '',
                    $refunded,
                    $charge['currency'] ?? null
                );
            } catch (\Throwable $e) {
                Log::warning('Affiliate clawback failed: ' . $e->getMessage());
            }
        }

        return $this->successMethod();
    }

    /**
     * The Implementation Service is a one-off Stripe Checkout charge
     * (mode=payment), not a subscription — Cashier has no built-in
     * handling for that, so this is entirely our own. Matched back to the
     * pending ImplementationOrder via the metadata set when the checkout
     * session was created (see BillingController::purchaseImplementation).
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];

        if (($session['mode'] ?? null) !== 'payment') {
            return $this->successMethod();
        }

        $orderId = $session['metadata']['implementation_order_id'] ?? null;

        if (! $orderId) {
            return $this->successMethod();
        }

        $order = ImplementationOrder::find($orderId);

        // 'abandoned' is deliberately not treated as settled here — see
        // ImplementationOrder::markPaidFromCheckoutSession()'s docblock.
        // A real, late payment must still be able to mark the order paid.
        if (! $order || in_array($order->status, ['paid', 'in_progress', 'completed'], true)) {
            return $this->successMethod();
        }

        $order->markPaidFromCheckoutSession($session['id']);

        Log::info('Implementation order paid.', [
            'implementation_order_id' => $order->id,
            'business_id' => $order->business_id,
            'stripe_checkout_session_id' => $session['id'],
        ]);

        return $this->successMethod();
    }

    /**
     * Turns a PendingRegistration into a real, usable Business + User —
     * the only place either one ever gets created for a paid signup. Runs
     * once, the first time Stripe confirms the subscription exists;
     * everything after that (plan_id syncing, cancellations, etc.) works
     * on the resulting Business exactly like any other.
     */
    private function finalizePendingRegistrationIfNeeded(array $payload): void
    {
        $data = $payload['data']['object'];
        $token = $data['metadata']['pending_registration_token'] ?? null;

        if (! $token) {
            return;
        }

        $pending = PendingRegistration::where('token', $token)->first();

        if (! $pending || $pending->isFinalized()) {
            return;
        }

        $customerId = $data['customer'] ?? null;

        if (! $customerId) {
            return;
        }

        // Guards against a redelivered webhook racing itself, or (in
        // principle) two subscriptions somehow sharing one Stripe
        // customer — never create a second Business for the same one.
        if (Business::withoutGlobalScopes()->where('stripe_id', $customerId)->exists()) {
            return;
        }

        DB::transaction(function () use ($pending, $customerId) {
            // Industry/starting-product selection deliberately doesn't
            // happen at signup anymore — it was confusing customers before
            // they'd even paid. They pick it after logging in instead; see
            // OnboardingController and the "Get Started" dashboard prompt.
            $business = Business::create([
                'name' => $pending->company_name,
                'country' => $pending->country,
                'state_province' => $pending->state_province,
                'quotation_disclaimer' => Business::defaultQuotationDisclaimer(),
            ]);

            // Not mass-assignable (Cashier's column, not meant to be set
            // by hand elsewhere) — this is the one place it's genuinely
            // correct to set it directly, since Stripe just told us this
            // customer belongs to this brand new business.
            $business->stripe_id = $customerId;
            $business->save();

            $user = User::create([
                'name' => $pending->name,
                'email' => $pending->email,
                'password' => $pending->password,
                'business_id' => $business->id,
            ]);

            $pending->update(['business_id' => $business->id]);

            // Referral attribution — the cookie captured at registration
            // was carried on the PendingRegistration; the Business only
            // exists now, in this webhook, so this is where it attaches.
            app(\App\Services\Affiliate\AttributionService::class)
                ->attach($business, $pending->affiliate_code);

            event(new Registered($user));

            Log::info('Pending registration finalized after payment.', [
                'pending_registration_id' => $pending->id,
                'business_id' => $business->id,
                'user_id' => $user->id,
            ]);
        });
    }

    /**
     * Looks at what Stripe says the subscription's status and price
     * actually are right now and updates plan_id to match — active or
     * trialing gets the plan whose stripe_price_id matches, anything else
     * (canceled, unpaid, incomplete_expired) clears it. past_due is left
     * alone: that's a grace period, not a loss of access, by design.
     */
    private function syncPlanFromSubscriptionPayload(array $payload): void
    {
        if (! $this->isDefaultSubscription($payload)) {
            return;
        }

        $data = $payload['data']['object'];

        $business = $this->getUserByStripeId($data['customer']);

        if (! $business instanceof Business) {
            return;
        }

        $status = $data['status'] ?? null;
        $revokingStatuses = ['canceled', 'unpaid', 'incomplete_expired'];

        if (in_array($status, $revokingStatuses, true)) {
            $business->update(['plan_id' => null]);

            return;
        }

        if (! in_array($status, ['active', 'trialing'], true)) {
            return;
        }

        // The checkout page builds subscriptions with an inline price_data
        // (so the tax-inclusive total can vary per business), which gets a
        // fresh, one-off Stripe price ID every time — that can never be
        // matched back to a Plan by ID, so plan_id is stashed directly in
        // the subscription's own metadata at checkout time instead (see
        // CheckoutController::confirm). Plans still sold through a fixed,
        // persisted Stripe Price (the older Billing-page flow) have no
        // such metadata, so this falls back to matching by price ID.
        $priceId = $data['items']['data'][0]['price']['id'] ?? null;
        $plan = null;

        if ($planId = $data['metadata']['plan_id'] ?? null) {
            $plan = Plan::find($planId);
        }

        if (! $plan && $priceId) {
            $plan = Plan::where('stripe_price_id', $priceId)->first();
        }

        if ($plan && $business->plan_id !== $plan->id) {
            $business->update(['plan_id' => $plan->id]);

            Log::info('Stripe subscription synced business to plan.', [
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'stripe_price_id' => $priceId,
            ]);
        }
    }

    /**
     * Stripe itself has no concept of "default" vs "support" — that's
     * purely Cashier's local naming (set via newSubscription($type, ...)).
     * The only reliable way to know which named subscription a webhook
     * event is about is to look up the local `subscriptions` row Cashier
     * just wrote (or already had) by its stripe_id and read its type.
     */
    private function isDefaultSubscription(array $payload): bool
    {
        $stripeSubscriptionId = $payload['data']['object']['id'] ?? null;

        if (! $stripeSubscriptionId) {
            return false;
        }

        $type = Subscription::where('stripe_id', $stripeSubscriptionId)->value('type');

        return $type === 'default';
    }
}
