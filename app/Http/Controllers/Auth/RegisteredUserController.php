<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use App\Models\Country;
use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        // Industry/starting-product selection deliberately doesn't happen
        // here anymore — it was confusing customers before they'd even
        // paid. They pick it after logging in instead; see
        // OnboardingController and the "Get Started" dashboard prompt.
        $selectedPlan = $request->filled('plan')
            ? Plan::where('is_active', true)->find($request->query('plan'))
            : null;

        // Every account must be created against a real plan (even the free
        // one) — this is the only door into registration, so someone who
        // lands here without a valid ?plan= gets sent to pick one instead
        // of silently falling through to an unauthorized free account.
        if (! $selectedPlan) {
            return redirect()->route('pricing');
        }

        // Canada and the US lead the list since they're the only countries
        // with a real state/province dropdown — everyone else sorts
        // alphabetically after them.
        $countries = Country::where('is_active', true)
            ->orderByRaw("CASE WHEN short_code = 'CA' THEN 0 WHEN short_code = 'US' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        return view('auth.register', compact('selectedPlan', 'countries'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'country' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
        ]);

        $plan = Plan::where('is_active', true)->find($request->plan_id);

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_id' => 'That plan is no longer available — please pick a plan again.',
            ]);
        }

        // A plan that's actually payable holds the account back from ever
        // existing until Stripe confirms payment — the registration data
        // is stashed behind a token instead, so someone who abandons
        // checkout never ends up with a real, logged-in, unpaid account.
        // See CheckoutController and Stripe\WebhookController, which
        // finalize this into a real Business + User once payment lands.
        if ($plan->stripe_price_id) {
            $pending = PendingRegistration::create([
                'name' => $request->name,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'country' => $request->country,
                'state_province' => $request->state_province,
                'plan_id' => $plan->id,
                'affiliate_code' => \App\Services\Affiliate\AttributionService::normalizeCode(
                    $request->cookie(\App\Services\Affiliate\AttributionService::COOKIE)
                ) ?: null,
            ]);

            return redirect()->route('checkout.show', $pending->token);
        }

        // Free plans skip Stripe entirely, so there's nothing else to stop
        // a script from minting unlimited accounts with made-up emails —
        // an emailed code stands in for the identity check a real credit
        // card gives every paid plan. Nothing is created yet; see
        // RegistrationOtpController::verify for the account creation.
        $pending = PendingRegistration::create([
            'name' => $request->name,
            'company_name' => $request->company_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'country' => $request->country,
            'state_province' => $request->state_province,
            'plan_id' => $plan->id,
        ]);

        // A mail server problem here must not strand someone on a signup
        // form that appears to have silently failed — the pending
        // registration already exists either way, so send them on to the
        // same verify screen regardless, just honest about whether a code
        // actually went out. "Resend" on that screen goes through this
        // same try/catch too, so they have a real way to retry.
        try {
            Mail::to($pending->email)->send(new RegistrationOtpMail($pending, $pending->issueOtp()));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('register.verify', $pending->token)
                ->with('status', "We couldn't send your verification code just now — click \"Resend\" below to try again.");
        }

        return redirect()->route('register.verify', $pending->token);
    }
}
