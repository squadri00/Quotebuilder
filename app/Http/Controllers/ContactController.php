<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormSubmitted;
use App\Models\PlatformSetting;
use App\Support\TurnstileVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact');
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Honeypot — a field real visitors never see or fill in (hidden
        // off-screen in the contact form), same pattern as the public
        // quote wizard's spam protection.
        if ($request->filled('website')) {
            return redirect()->route('contact')->with('contactSuccess', true);
        }

        if (! app(TurnstileVerifier::class)->passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput()->withErrors(['turnstile' => "We couldn't verify you're not a robot — please try again."]);
        }

        $to = PlatformSetting::get()->contact_email ?: config('mail.from.address');

        Mail::to($to)->send(new ContactFormSubmitted(
            $validated['name'],
            $validated['email'],
            $validated['message'],
        ));

        return redirect()->route('contact')->with('contactSuccess', true);
    }
}
