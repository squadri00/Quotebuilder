<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BusinessSettingsController extends Controller
{
    public function updateDetails(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'timezone'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'quotation_disclaimer' => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()->business->update($validated);

        return back()->with('status', 'business-details-updated');
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $business = $request->user()->business;

        if ($request->hasFile('logo')) {
            if ($business->logo_path) {
                Storage::disk('public')->delete($business->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('business-logos', 'public');
        }

        unset($validated['logo']);

        $business->update($validated);

        return back()->with('status', 'business-branding-updated');
    }

    /**
     * Settings that only affect the public, no-login quote flow customers
     * use — never internal, staff-entered quotes. The download/email
     * toggles here can only ever turn something OFF: PublicQuoteController
     * still checks the business's actual plan feature first, so a
     * business without pdf_download/email_notifications on their plan
     * gets no effect from switching these on. See
     * PublicQuoteController::hasFeatureForQuoting() and its call sites.
     */
    public function updatePublicQuoteSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'public_quote_validity_days' => ['nullable', Rule::in(Quote::EXPIRATION_DAY_OPTIONS)],
        ]);

        $validated['public_pdf_download_enabled'] = $request->boolean('public_pdf_download_enabled');
        $validated['public_email_enabled'] = $request->boolean('public_email_enabled');

        $request->user()->business->update($validated);

        return back()->with('status', 'business-public-quote-settings-updated');
    }

    /**
     * The reference number shown to customers/staff on quotes — see
     * Business::nextQuoteReferenceNumber() and Quote::displayReference().
     * quote_number_next is deliberately editable at any time (not just once
     * at setup): a business might want to jump the sequence, e.g. to match
     * an existing paper invoice book. It only ever affects quotes created
     * from this point forward — already-issued reference numbers are
     * frozen on their Quote row and never renumbered.
     */
    public function updateQuoteNumbering(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quote_number_format' => ['required', Rule::in(['numeric', 'alphanumeric'])],
            'quote_number_prefix' => ['nullable', 'string', 'max:20'],
            'quote_number_next' => ['required', 'integer', 'min:1'],
        ]);

        $request->user()->business->update($validated);

        return back()->with('status', 'business-quote-numbering-updated');
    }
}
