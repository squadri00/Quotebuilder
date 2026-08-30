<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends PortalController
{
    public function edit(): View
    {
        return view('affiliate.portal.profile', [
            'partner' => $this->partner(),
            'countries' => Country::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'payout_method' => ['nullable', 'string', 'max:40'],
            'payout_details' => ['nullable', 'string', 'max:255'],
        ]);

        $this->partner()->update($data);

        return back()->with('status', 'Profile saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($data['current'], $this->partner()->password)) {
            return back()->withErrors(['current' => 'Your current password is incorrect.']);
        }

        $this->partner()->update(['password' => $data['password']]);

        return back()->with('status', 'Password updated.');
    }
}
