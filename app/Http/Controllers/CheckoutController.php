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
 * form, then sends the plan's real fixed Stripe price plus the matching
 * Stripe Tax Rate (see Business::taxRates()) to build the subscription —
 * so unlike an earlier version of this flow, tax isn't merged into a
 * one-off frozen price; Stripe re-applies it fresh on every renewal.
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

        // No Business/Stripe customer exists yet, so this is a guest
        // checkout (Stripe creates the customer itself once payment
        // completes) rather than $business->newSubscription(...). The
        // subscription's own metadata carries both plan_id and the
        // pending registration's token — that's what the webhook reads to
        // create the real account and sync its plan (see
        // Stripe\WebhookController).
        //
        // Uses the plan's real, fixed, tax-exclusive stripe_price_id —
        // NOT a one-off price_data with tax merged into the amount. Tax
        // is attached separately below as default_tax_rates, which Stripe
        // then re-applies on every future renewal invoice on its own
        // (unlike the old merged-price approach, which froze that first
        // charge's tax amount forever). This is the exact same mechanism
        // as Business::taxRates() — just computed by hand here since no
        // real Business exists yet to call that method on.
        return Checkout::guest()->create([[
            'price' => $plan->stripe_price_id,
            'quantity' => 1,
        ]], [
            'mode' => 'subscription',
            'subscription_data' => array_filter([
                'default_tax_rates' => $this->taxRateIdsForPending($pending) ?: null,
                'metadata' => [
                    'name' => 'default',
                    'type' => 'default',
                    'plan_id' => (string) $plan->id,
                    'pending_registration_token' => $pending->token,
                ],
            ]),
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

    /**
     * @return string[] Stripe Tax Rate IDs to attach — see
     *   Business::taxRates(), whose exact logic this mirrors for a
     *   pending registrant that isn't a real Business yet.
     */
    private function taxRateIdsForPending(PendingRegistration $pending): array
    {
        return (new Business([
            'country' => $pending->country,
            'state_province' => $pending->state_province,
        ]))->taxRates();
    }
}
