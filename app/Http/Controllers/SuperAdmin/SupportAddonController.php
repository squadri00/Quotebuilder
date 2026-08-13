<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportAddon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportAddonController extends Controller
{
    public function edit(): View
    {
        $supportAddon = SupportAddon::get();

        return view('superadmin.support-addon.edit', compact('supportAddon'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $supportAddon = SupportAddon::get();
        $supportAddon->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.support-addon.edit')->with('status', 'Support add-on settings updated.');
    }
}
