<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use App\Models\PendingRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The email-code step that stands between submitting the free-plan
 * signup form and an account actually existing — see
 * RegisteredUserController::store(), which stashes the registration
 * behind a PendingRegistration instead of creating anything up front.
 */
class RegistrationOtpController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $pending = $this->findUnfinishedFreeRegistration($token);

        if (! $pending) {
            return redirect()->route('register')->with('status', 'That signup link is no longer valid — please sign up again.');
        }

        return view('auth.verify-otp', ['pending' => $pending]);
    }

    /**
     * @throws ValidationException
     */
    public function verify(Request $request, string $token): RedirectResponse
    {
        $pending = $this->findUnfinishedFreeRegistration($token);

        if (! $pending) {
            return redirect()->route('register')->with('status', 'That signup link is no longer valid — please sign up again.');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if ($pending->otpIsExpired()) {
            throw ValidationException::withMessages([
                'code' => 'That code has expired — request a new one below.',
            ]);
        }

        if (! $pending->otpMatches($request->string('code')->trim())) {
            throw ValidationException::withMessages([
                'code' => 'That code is incorrect — please try again.',
            ]);
        }

        $user = $pending->finalizeToBusiness();

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false))
            ->with('status', "Welcome! You're on the \"{$pending->plan->name}\" plan.");
    }

    /**
     * @throws ValidationException
     */
    public function resend(string $token): RedirectResponse
    {
        $pending = $this->findUnfinishedFreeRegistration($token);

        if (! $pending) {
            return redirect()->route('register')->with('status', 'That signup link is no longer valid — please sign up again.');
        }

        Mail::to($pending->email)->send(new RegistrationOtpMail($pending, $pending->issueOtp()));

        return redirect()->route('register.verify', $pending->token)
            ->with('status', 'We sent you a new code.');
    }

    /**
     * Only ever matches a still-open free-plan signup — a paid plan (or
     * one already finalized) has no business going through this screen.
     */
    private function findUnfinishedFreeRegistration(string $token): ?PendingRegistration
    {
        return PendingRegistration::whereNull('business_id')
            ->whereHas('plan', fn ($query) => $query->whereNull('stripe_price_id'))
            ->where('token', $token)
            ->first();
    }
}
