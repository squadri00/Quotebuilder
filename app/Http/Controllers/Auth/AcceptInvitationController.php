<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Public, token-gated — nobody is authenticated when accepting a team
 * invitation. Mirrors the account-not-created-until-confirmed pattern
 * used for paid signups (see PendingRegistration): no User row exists for
 * an invitee until they've actually set a password here.
 */
class AcceptInvitationController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $invitation = TeamInvitation::withoutGlobalScope('business')->where('token', $token)->first();

        if (! $invitation) {
            abort(404);
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('status', 'This invitation has already been accepted — log in below.');
        }

        if ($invitation->isExpired()) {
            return view('auth.invitation-expired', compact('invitation'));
        }

        return view('auth.accept-invitation', compact('invitation'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = TeamInvitation::withoutGlobalScope('business')->where('token', $token)->first();

        if (! $invitation || $invitation->isAccepted()) {
            abort(404);
        }

        abort_if($invitation->isExpired(), 410, 'This invitation has expired — ask the account owner to resend it.');

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
                'business_id' => $invitation->business_id,
                'role' => $invitation->role,
                'permissions' => $invitation->permissions ?? [],
            ]);

            if (! empty($invitation->product_ids)) {
                $user->products()->sync($invitation->product_ids);
            }

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', "Welcome to {$invitation->business->name}!");
    }
}
