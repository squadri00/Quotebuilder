<?php

namespace App\Console\Commands;

use App\Models\ImplementationOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

/**
 * Implementation Service orders are created the instant a customer
 * clicks Purchase — before Stripe confirms anything — because Stripe
 * Checkout needs an order id to hand back on success (see
 * BillingController::purchaseImplementation's docblock). That means a
 * customer who opens checkout and never pays leaves a permanent
 * 'awaiting_payment' row behind with nothing to ever clear it.
 *
 * This command asks Stripe directly, per order, what actually happened
 * to that specific checkout link:
 *   - session status 'expired' -> the customer genuinely never paid and
 *     can no longer pay through that link. Safe to mark 'abandoned'.
 *   - session status 'complete' -> a payment DID succeed but the webhook
 *     that should have marked it paid never arrived (the exact class of
 *     bug found earlier with local webhook delivery). Self-heal by
 *     marking it paid via the same helper the webhook itself uses.
 *   - anything else (still 'open') -> the customer may still come back
 *     and pay; leave it alone.
 *
 * Only ever acts on orders past a grace period, and only ever trusts
 * Stripe's own session status — never a locally-guessed time window —
 * so a late-but-genuine payment can never be mistaken for abandonment.
 */
class ExpireAbandonedImplementationOrders extends Command
{
    protected $signature = 'implementation-orders:expire-abandoned';

    protected $description = 'Reconcile stale awaiting-payment Implementation Orders against Stripe: mark truly abandoned checkouts abandoned, and recover any paid order whose webhook never arrived.';

    public function handle(): int
    {
        $candidates = ImplementationOrder::where('status', 'awaiting_payment')
            ->whereNotNull('stripe_checkout_session_id')
            ->where('created_at', '<=', now()->subHour())
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No stale awaiting-payment orders to check.');

            return self::SUCCESS;
        }

        $stripe = Cashier::stripe();
        $abandoned = 0;
        $recovered = 0;

        foreach ($candidates as $order) {
            try {
                $session = $stripe->checkout->sessions->retrieve($order->stripe_checkout_session_id);
            } catch (\Throwable $e) {
                Log::warning('Could not retrieve Stripe checkout session while expiring implementation orders.', [
                    'implementation_order_id' => $order->id,
                    'stripe_checkout_session_id' => $order->stripe_checkout_session_id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($session->status === 'expired') {
                $order->markAbandoned();
                $abandoned++;

                Log::info('Implementation order marked abandoned — checkout session expired unpaid.', [
                    'implementation_order_id' => $order->id,
                    'business_id' => $order->business_id,
                ]);
            } elseif ($session->status === 'complete' && $session->payment_status === 'paid') {
                $order->markPaidFromCheckoutSession($session->id);
                $recovered++;

                Log::warning('Implementation order recovered — Stripe shows it was paid but no webhook ever marked it. Possible webhook delivery gap.', [
                    'implementation_order_id' => $order->id,
                    'business_id' => $order->business_id,
                ]);
            }
        }

        $this->info("Checked {$candidates->count()} order(s): {$abandoned} marked abandoned, {$recovered} recovered from a missed webhook.");

        return self::SUCCESS;
    }
}
