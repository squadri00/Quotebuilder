<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Services\PlatformTaxCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Cashier\Checkout;

/**
 * The plan checkout review page shown right after registration: shows a
 * real tax breakdown computed from the address entered on the signup
 * form, and — unlike the informational split shown elsewhere on the
 * Billing page — actually sends that tax-inclusive total to Stripe as
 * the subscription price, built fresh per checkout via price_data.
 *
 * No Business/User exists yet at this point — the registration is just a
 * PendingRegistration behind a token, so an abandoned checkout never
 * leaves behind a real, logged-in, unpaid account. The real account is
 * only created once Stripe confirms payment (see
 * Stripe\WebhookController::finalizePendingRegistration), which is also
 * the only place login happens.
 */
class CheckoutController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $pending = PendingRegistration::where('token', $token)->firstOrFail();

        if ($pending->isFinalized()) {
            return redirect()->route('checkout.success', $token);
        }

        $plan = $pending->plan;

        abort_unless($plan && $plan->is_active && $plan->stripe_price_id, 404, 'This plan is not set up for Stripe billing.');

        $tax = $this->taxForPending($pending, $plan);

        return view('checkout.show', ['plan' => $plan, 'pending' => $pending, 'tax' => $tax]);
    }

    /**
     * No request body needed — country/state_province were already
     * captured once on the registration form (required there whenever a
     * paid plan is picked; see partials/register-form.blade.php) and
     * aren't editable again here.
     */
    public function confirm(string $token): RedirectResponse|Checkout
    {
        $pending = PendingRegistration::where('token', $token)->firstOrFail();

        abort_if($pending->isFinalized(), 404);

        $plan = $pending->plan;

        abort_unless($plan && $plan->is_active && $plan->stripe_price_id, 404, 'This plan is not set up for Stripe billing.');

        $tax = $this->taxForPending($pending, $plan);
        $interval = $plan->billing_interval === 'yearly' ? 'year' : 'month';

        // No Business/Stripe customer exists yet, so this is a guest
        // checkout (Stripe creates the customer itself once payment
        // completes) rather than $business->newSubscription(...). The
        // subscription's own metadata carries both plan_id and the
        // pending registration's token — that's what the webhook reads to
        // create the real account and sync its plan (see
        // Stripe\WebhookController).
        return Checkout::guest()->create([[
            'price_data' => [
                'currency' => (new Business)->preferredCurrency(),
                'unit_amount' => (int) round($tax['total'] * 100),
                'recurring' => ['interval' => $interval],
                'product_data' => ['name' => $plan->name],
            ],
            'quantity' => 1,
        ]], [
            'mode' => 'subscription',
            'subscription_data' => [
                'metadata' => [
                    'name' => 'default',
                    'type' => 'default',
                    'plan_id' => (string) $plan->id,
                    'pending_registration_token' => $pending->token,
                ],
            ],
            'success_url' => route('checkout.success', $pending->token).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.show', $pending->token).'?checkout=cancelled',
        ]);
    }

    /**
     * Where Stripe sends the customer back after checkout. Login only
     * ever happens here (and only once the webhook has actually finalized
     * the account) — never at registration time.
     */
    public function success(string $token): View|RedirectResponse
    {
        $pending = PendingRegistration::where('token', $token)->firstOrFail();

        if (! $pending->isFinalized()) {
            return view('checkout.waiting', ['token' => $token]);
        }

        $user = $pending->business->users()->first();

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'Welcome! Your subscription is active.');
    }

    private function taxForPending(PendingRegistration $pending, Plan $plan): array
    {
        $business = new Business([
            'country' => $pending->country,
            'state_province' => $pending->state_province,
        ]);

        return (new PlatformTaxCalculator)->addToBase($business, (float) $plan->price);
    }
}
