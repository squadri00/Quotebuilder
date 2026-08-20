<?php

namespace Tests\Feature;

use App\Http\Controllers\Stripe\WebhookController;
use App\Models\Business;
use App\Models\ImplementationOrder;
use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exercises App\Http\Controllers\Stripe\WebhookController's own business
 * logic (finalizing pending registrations, syncing plan_id, the
 * Implementation Service one-off charge) directly against the controller
 * method — bypassing HTTP routing and Cashier's VerifyWebhookSignature
 * middleware entirely, the same way every other controller in this app's
 * test/verification passes has been exercised throughout this project.
 * This is deliberate: it isolates "does our webhook logic do the right
 * thing" from "is this request cryptographically really from Stripe,"
 * which is Cashier's own well-tested concern, not this app's.
 *
 * Every payload here is a hand-built minimal fixture matching the real
 * shape Stripe sends for that event type — not a full recorded payload,
 * just the fields this app's handlers actually read.
 */
class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function dispatch(string $type, array $object): void
    {
        $payload = ['type' => $type, 'data' => ['object' => $object]];

        $request = Request::create('/stripe/webhook', 'POST', [], [], [], [], json_encode($payload));
        $request->headers->set('Content-Type', 'application/json');

        app(WebhookController::class)->handleWebhook($request);
    }

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Growth',
            'price' => 49,
            'billing_interval' => 'monthly',
            'discount_display' => 'fixed',
            'is_active' => true,
        ], $overrides));
    }

    public function test_subscription_created_finalizes_pending_registration_and_assigns_plan(): void
    {
        $plan = $this->plan(['stripe_price_id' => 'price_growth_monthly']);

        $pending = PendingRegistration::create([
            'name' => 'Jane Owner',
            'company_name' => 'Jane\'s Print Shop',
            'email' => 'jane@example.test',
            'password' => bcrypt('secret-password'),
            'country' => 'CA',
            'state_province' => 'ON',
            'plan_id' => $plan->id,
        ]);

        $this->assertSame(0, Business::withoutGlobalScopes()->count());

        $this->dispatch('customer.subscription.created', [
            'id' => 'sub_test_123',
            'customer' => 'cus_test_abc',
            'status' => 'active',
            'items' => ['data' => [['id' => 'si_1', 'price' => ['id' => 'price_growth_monthly', 'product' => 'prod_1']]]],
            'metadata' => [
                'pending_registration_token' => $pending->token,
                'plan_id' => (string) $plan->id,
            ],
        ]);

        $business = Business::withoutGlobalScopes()->where('stripe_id', 'cus_test_abc')->first();

        $this->assertNotNull($business, 'Webhook should have created a real Business.');
        $this->assertSame('Jane\'s Print Shop', $business->name);
        $this->assertSame($plan->id, $business->plan_id);

        $user = User::withoutGlobalScopes()->where('email', 'jane@example.test')->first();
        $this->assertNotNull($user, 'Webhook should have created a real User.');
        $this->assertSame($business->id, $user->business_id);

        $pending->refresh();
        $this->assertTrue($pending->isFinalized());
    }

    public function test_redelivered_subscription_created_does_not_create_a_duplicate_business(): void
    {
        $plan = $this->plan(['stripe_price_id' => 'price_growth_monthly']);

        $pending = PendingRegistration::create([
            'name' => 'Jane Owner',
            'company_name' => 'Jane\'s Print Shop',
            'email' => 'jane@example.test',
            'password' => bcrypt('secret-password'),
            'country' => 'CA',
            'state_province' => 'ON',
            'plan_id' => $plan->id,
        ]);

        $object = [
            'id' => 'sub_test_123',
            'customer' => 'cus_test_abc',
            'status' => 'active',
            'items' => ['data' => [['id' => 'si_1', 'price' => ['id' => 'price_growth_monthly', 'product' => 'prod_1']]]],
            'metadata' => ['pending_registration_token' => $pending->token, 'plan_id' => (string) $plan->id],
        ];

        $this->dispatch('customer.subscription.created', $object);
        $this->dispatch('customer.subscription.created', $object); // Stripe redelivering the same event

        $this->assertSame(
            1,
            Business::withoutGlobalScopes()->where('stripe_id', 'cus_test_abc')->count(),
            'A redelivered webhook must never create a second Business for the same Stripe customer.'
        );
    }

    public function test_subscription_updated_to_active_assigns_plan_by_metadata(): void
    {
        $plan = $this->plan(['name' => 'Professional', 'price' => 79]);
        $business = $this->businessWithSubscription('sub_test_456', 'default');

        $this->dispatch('customer.subscription.updated', [
            'id' => 'sub_test_456',
            'customer' => $business->stripe_id,
            'status' => 'active',
            'items' => ['data' => [['id' => 'si_2', 'price' => ['id' => 'price_one_off_xyz', 'product' => 'prod_2']]]],
            'metadata' => ['plan_id' => (string) $plan->id],
        ]);

        $this->assertSame($plan->id, $business->fresh()->plan_id);
    }

    public function test_subscription_updated_to_past_due_does_not_clear_plan(): void
    {
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_test_789', 'default', $plan->id);

        $this->dispatch('customer.subscription.updated', [
            'id' => 'sub_test_789',
            'customer' => $business->stripe_id,
            'status' => 'past_due',
            'items' => ['data' => [['id' => 'si_3', 'price' => ['id' => 'price_whatever', 'product' => 'prod_3']]]],
            'metadata' => [],
        ]);

        $this->assertSame(
            $plan->id,
            $business->fresh()->plan_id,
            'past_due is a grace period by design — access must not be revoked yet.'
        );
    }

    public function test_subscription_updated_to_canceled_clears_plan(): void
    {
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_test_999', 'default', $plan->id);

        $this->dispatch('customer.subscription.updated', [
            'id' => 'sub_test_999',
            'customer' => $business->stripe_id,
            'status' => 'canceled',
            'items' => ['data' => [['id' => 'si_4', 'price' => ['id' => 'price_whatever', 'product' => 'prod_4']]]],
            'metadata' => [],
        ]);

        $this->assertNull($business->fresh()->plan_id);
    }

    public function test_subscription_deleted_on_default_subscription_clears_plan(): void
    {
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_default_1', 'default', $plan->id);

        $this->dispatch('customer.subscription.deleted', [
            'id' => 'sub_default_1',
            'customer' => $business->stripe_id,
            'status' => 'canceled',
        ]);

        $this->assertNull($business->fresh()->plan_id);
    }

    public function test_subscription_deleted_on_support_addon_does_not_touch_main_plan(): void
    {
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_default_2', 'default', $plan->id);

        // The business also has a separate Priority Support subscription —
        // cancelling *that one* must never clear the main plan.
        DB::table('subscriptions')->insert([
            'business_id' => $business->id,
            'type' => 'support',
            'stripe_id' => 'sub_support_1',
            'stripe_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->dispatch('customer.subscription.deleted', [
            'id' => 'sub_support_1',
            'customer' => $business->stripe_id,
            'status' => 'canceled',
        ]);

        $this->assertSame(
            $plan->id,
            $business->fresh()->plan_id,
            'Cancelling the Priority Support add-on must not revoke the main plan.'
        );
    }

    public function test_invoice_payment_failed_does_not_throw_and_leaves_plan_untouched(): void
    {
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_test_inv', 'default', $plan->id);

        $this->dispatch('invoice.payment_failed', [
            'id' => 'in_test_1',
            'customer' => $business->stripe_id,
        ]);

        // No exception thrown is itself the main assertion here — this
        // handler is deliberately a no-op beyond logging (see its
        // docblock: the real reaction happens via subscription.updated).
        $this->assertSame($plan->id, $business->fresh()->plan_id);
    }

    public function test_checkout_session_completed_marks_implementation_order_paid(): void
    {
        $business = Business::create(['name' => 'Acme Co']);
        $order = ImplementationOrder::create([
            'business_id' => $business->id,
            'tier_name' => 'Starter Build-Out',
            'product_count' => 5,
            'price' => 500,
            'status' => 'awaiting_payment',
        ]);

        $this->dispatch('checkout.session.completed', [
            'id' => 'cs_test_1',
            'mode' => 'payment',
            'metadata' => ['implementation_order_id' => (string) $order->id],
        ]);

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('cs_test_1', $order->stripe_checkout_session_id);
        $this->assertNotNull($order->paid_at);
    }

    public function test_checkout_session_completed_ignores_subscription_mode_sessions(): void
    {
        $business = Business::create(['name' => 'Acme Co']);
        $order = ImplementationOrder::create([
            'business_id' => $business->id,
            'tier_name' => 'Starter Build-Out',
            'product_count' => 5,
            'price' => 500,
            'status' => 'awaiting_payment',
        ]);

        // A subscription checkout (mode=subscription) completing should
        // never touch an unrelated Implementation Order — the handler
        // must only act on mode=payment sessions.
        $this->dispatch('checkout.session.completed', [
            'id' => 'cs_test_2',
            'mode' => 'subscription',
            'metadata' => ['implementation_order_id' => (string) $order->id],
        ]);

        $this->assertSame('awaiting_payment', $order->fresh()->status);
    }

    public function test_checkout_session_completed_is_idempotent_for_already_paid_orders(): void
    {
        $business = Business::create(['name' => 'Acme Co']);
        $order = ImplementationOrder::create([
            'business_id' => $business->id,
            'tier_name' => 'Starter Build-Out',
            'product_count' => 5,
            'price' => 500,
            'status' => 'paid',
            'stripe_checkout_session_id' => 'cs_original',
            'paid_at' => now()->subDay(),
        ]);

        $originalPaidAt = $order->paid_at;

        $this->dispatch('checkout.session.completed', [
            'id' => 'cs_redelivered',
            'mode' => 'payment',
            'metadata' => ['implementation_order_id' => (string) $order->id],
        ]);

        $order->refresh();
        $this->assertSame('cs_original', $order->stripe_checkout_session_id, 'A redelivered event must not overwrite an already-paid order.');
        $this->assertEquals($originalPaidAt->timestamp, $order->paid_at->timestamp);
    }

    /**
     * 'abandoned' is a best-effort label (set once a checkout link looks
     * stale — see ExpireAbandonedImplementationOrders), never a guarantee
     * Stripe can no longer accept payment on it. A real, late payment
     * must still be able to mark the order paid rather than being
     * silently dropped because the order wasn't sitting in
     * 'awaiting_payment' anymore.
     */
    public function test_checkout_session_completed_recovers_an_abandoned_order(): void
    {
        $business = Business::create(['name' => 'Acme Co']);
        $order = ImplementationOrder::create([
            'business_id' => $business->id,
            'tier_name' => 'Starter Build-Out',
            'product_count' => 5,
            'price' => 500,
            'status' => 'abandoned',
        ]);

        $this->dispatch('checkout.session.completed', [
            'id' => 'cs_late_payment',
            'mode' => 'payment',
            'metadata' => ['implementation_order_id' => (string) $order->id],
        ]);

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('cs_late_payment', $order->stripe_checkout_session_id);
        $this->assertNotNull($order->paid_at);
    }

    public function test_invoice_payment_succeeded_records_tax_collected(): void
    {
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_tax_1', 'default', $plan->id);

        $this->dispatch('invoice.payment_succeeded', [
            'id' => 'in_tax_1',
            'customer' => $business->stripe_id,
            'currency' => 'cad',
            'subtotal' => 4900,
            'total' => 5537,
            'parent' => ['subscription_details' => ['subscription' => 'sub_tax_1']],
            'total_taxes' => [
                ['amount' => 637, 'tax_rate_details' => ['tax_rate' => 'txr_ontario']],
            ],
        ]);

        $record = \App\Models\TaxCollection::where('stripe_invoice_id', 'in_tax_1')->first();

        $this->assertNotNull($record);
        $this->assertSame($business->id, $record->business_id);
        $this->assertSame('default', $record->subscription_type);
        $this->assertSame('6.37', $record->amount);
        $this->assertSame('49.00', $record->base_amount);
        $this->assertSame('55.37', $record->total_amount);
        $this->assertSame('CAD', $record->currency);
    }

    public function test_invoice_payment_succeeded_is_idempotent_on_redelivery(): void
    {
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_tax_2', 'default', $plan->id);

        $object = [
            'id' => 'in_tax_2',
            'customer' => $business->stripe_id,
            'currency' => 'cad',
            'parent' => ['subscription_details' => ['subscription' => 'sub_tax_2']],
            'total_taxes' => [['amount' => 637, 'tax_rate_details' => ['tax_rate' => 'txr_ontario']]],
        ];

        $this->dispatch('invoice.payment_succeeded', $object);
        $this->dispatch('invoice.payment_succeeded', $object); // Stripe redelivering the same event

        $this->assertSame(
            1,
            \App\Models\TaxCollection::where('stripe_invoice_id', 'in_tax_2')->count(),
            'A redelivered webhook must never double-record the same invoice.'
        );
    }

    public function test_invoice_payment_succeeded_still_records_revenue_when_no_tax_was_charged(): void
    {
        // A non-Canadian business's renewal — no tax attached, but it must
        // still be logged so the Financial Activity report's revenue
        // totals aren't silently missing every non-Canadian payment.
        $plan = $this->plan();
        $business = $this->businessWithSubscription('sub_tax_3', 'default', $plan->id);

        $this->dispatch('invoice.payment_succeeded', [
            'id' => 'in_tax_3',
            'customer' => $business->stripe_id,
            'currency' => 'usd',
            'subtotal' => 4900,
            'total' => 4900,
            'parent' => ['subscription_details' => ['subscription' => 'sub_tax_3']],
            'total_taxes' => [],
        ]);

        $record = \App\Models\TaxCollection::where('stripe_invoice_id', 'in_tax_3')->first();

        $this->assertNotNull($record, 'Revenue must still be logged even when no tax applies.');
        $this->assertSame('0.00', $record->amount);
        $this->assertSame('49.00', $record->base_amount);
        $this->assertSame('49.00', $record->total_amount);
    }

    private function businessWithSubscription(string $stripeSubscriptionId, string $type, ?int $planId = null): Business
    {
        $business = Business::create([
            'name' => 'Test Business '.$stripeSubscriptionId,
            'plan_id' => $planId,
        ]);
        $business->stripe_id = 'cus_'.$stripeSubscriptionId;
        $business->save();

        DB::table('subscriptions')->insert([
            'business_id' => $business->id,
            'type' => $type,
            'stripe_id' => $stripeSubscriptionId,
            'stripe_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $business;
    }
}
