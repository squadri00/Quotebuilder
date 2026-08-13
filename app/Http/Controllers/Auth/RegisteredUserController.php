<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        // Industry/starting-product selection deliberately doesn't happen
        // here anymore — it was confusing customers before they'd even
        // paid. They pick it after logging in instead; see
        // OnboardingController and the "Get Started" dashboard prompt.
        $selectedPlan = $request->filled('plan')
            ? Plan::where('is_active', true)->find($request->query('plan'))
            : null;

        return view('auth.register', compact('selectedPlan'));
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
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'country' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
        ]);

        $plan = $request->filled('plan_id')
            ? Plan::where('is_active', true)->find($request->plan_id)
            : null;

        // A plan that's actually payable holds the account back from ever
        // existing until Stripe confirms payment — the registration data
        // is stashed behind a token instead, so someone who abandons
        // checkout never ends up with a real, logged-in, unpaid account.
        // See CheckoutController and Stripe\WebhookController, which
        // finalize this into a real Business + User once payment lands.
        if ($plan && $plan->stripe_price_id) {
            $pending = PendingRegistration::create([
                'name' => $request->name,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'country' => $request->country,
                'state_province' => $request->state_province,
                'plan_id' => $plan->id,
            ]);

            return redirect()->route('checkout.show', $pending->token);
        }

        $user = DB::transaction(function () use ($request) {
            $business = Business::create([
                'name' => $request->company_name,
                'country' => $request->country,
                'state_province' => $request->state_province,
                'quotation_disclaimer' => Business::defaultQuotationDisclaimer(),
            ]);

            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'business_id' => $business->id,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        if ($plan) {
            return redirect(route('dashboard', absolute: false))
                ->with('status', "You selected the \"{$plan->name}\" plan — billing for it isn't fully set up yet. We'll be in touch, or check Billing once it's ready.");
        }

        return redirect(route('dashboard', absolute: false));
    }
}
