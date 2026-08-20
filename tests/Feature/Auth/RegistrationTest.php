<?php

namespace Tests\Feature\Auth;

use App\Models\Country;
use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Services\PlatformTaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Registration has required picking a real plan first since the
     * paid-signup rework (see RegisteredUserController::create) — landing
     * on /register with no ?plan= sends you to pick one instead of ever
     * rendering a form.
     */
    public function test_registration_screen_redirects_to_pricing_without_a_plan(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('pricing'));
    }

    public function test_registration_screen_can_be_rendered_with_a_valid_plan(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'price' => 0, 'is_active' => true]);

        $response = $this->get('/register?plan='.$plan->id);

        $response->assertStatus(200);
    }

    /**
     * Free plans skip Stripe but still aren't handed a live account
     * immediately — an emailed verification code stands in for the
     * identity check a paid plan gets from Stripe. Registering only ever
     * creates a PendingRegistration; the real Business/User is created by
     * RegistrationOtpController::verify once the code is confirmed.
     */
    public function test_registering_for_a_free_plan_creates_a_pending_registration_and_sends_to_verification(): void
    {
        $plan = Plan::create(['name' => 'Free', 'price' => 0, 'is_active' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan_id' => $plan->id,
        ]);

        $this->assertGuest();

        $pending = PendingRegistration::where('email', 'test@example.com')->first();
        $this->assertNotNull($pending, 'Expected a PendingRegistration to be created.');
        $this->assertSame($plan->id, $pending->plan_id);

        $response->assertRedirect(route('register.verify', $pending->token));
    }

    /**
     * A plan with a real Stripe price attached holds the account back
     * until Stripe actually confirms payment — registering sends straight
     * to checkout rather than the OTP flow (see
     * Stripe\WebhookController::finalizePendingRegistrationIfNeeded for
     * where the real Business/User eventually gets created).
     */
    public function test_registering_for_a_paid_plan_creates_a_pending_registration_and_sends_to_checkout(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'price' => 29, 'stripe_price_id' => 'price_test_123', 'is_active' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'paid@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan_id' => $plan->id,
        ]);

        $this->assertGuest();

        $pending = PendingRegistration::where('email', 'paid@example.com')->first();
        $this->assertNotNull($pending, 'Expected a PendingRegistration to be created.');

        $response->assertRedirect(route('checkout.show', $pending->token));
    }

    /**
     * Registration accepts any country listed in the Countries table
     * (Super Admin > Countries), not just Canada/US — the field itself
     * was always free-text server-side (see
     * RegisteredUserController::store()'s validation), this just proves
     * a non-CA/US value round-trips correctly and still gets no platform
     * tax, exactly as before this was opened up.
     */
    public function test_registering_from_a_non_home_country_stores_it_and_charges_no_tax(): void
    {
        Country::create(['name' => 'Ireland', 'short_code' => 'IE', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_position' => 'before']);
        $plan = Plan::create(['name' => 'Starter', 'price' => 29, 'stripe_price_id' => 'price_test_123', 'is_active' => true]);

        $response = $this->post('/register', [
            'name' => 'Siobhan QA',
            'company_name' => 'Dublin Test Co',
            'country' => 'Ireland',
            'state_province' => 'County Dublin',
            'email' => 'siobhan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan_id' => $plan->id,
        ]);

        $pending = PendingRegistration::where('email', 'siobhan@example.com')->first();
        $this->assertNotNull($pending);
        $this->assertSame('Ireland', $pending->country);
        $this->assertSame('County Dublin', $pending->state_province);

        $response->assertRedirect(route('checkout.show', $pending->token));

        $business = new \App\Models\Business(['country' => $pending->country, 'state_province' => $pending->state_province]);
        $tax = (new PlatformTaxCalculator)->splitFromTotal($business, (float) $plan->price);
        $this->assertSame(0.0, $tax['tax'], 'Non-Canadian countries must never be charged platform tax.');
    }
}
