<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformTaxRate;
use App\Support\ProvinceCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Cashier\Cashier;

class PlatformTaxRateController extends Controller
{
    public function index(): View
    {
        $rates = PlatformTaxRate::orderByRaw('province IS NULL')->orderBy('province')->get();

        return view('superadmin.platform-tax-rates.index', compact('rates'));
    }

    public function create(): View
    {
        return view('superadmin.platform-tax-rates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $rate = PlatformTaxRate::create([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncStripeTaxRate($rate);

        return redirect()->route('superadmin.platform-tax-rates.index')->with('status', 'Tax rate added.');
    }

    public function edit(PlatformTaxRate $platformTaxRate): View
    {
        return view('superadmin.platform-tax-rates.edit', ['rate' => $platformTaxRate]);
    }

    public function update(Request $request, PlatformTaxRate $platformTaxRate): RedirectResponse
    {
        $validated = $this->validated($request);
        $rateChanged = (float) $validated['rate'] !== (float) $platformTaxRate->rate;

        $platformTaxRate->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Stripe Tax Rate objects are immutable once created — a changed
        // percentage needs a brand new one, not an edit. The old object is
        // simply never referenced again (Stripe keeps it, harmlessly
        // unused) rather than something this app tries to delete or
        // deactivate, since other historical invoices may still point at
        // it and Stripe itself recommends against reusing a rate id
        // across different percentages.
        if ($rateChanged) {
            $platformTaxRate->update(['stripe_tax_rate_id' => null]);
        }

        $this->syncStripeTaxRate($platformTaxRate);

        return redirect()->route('superadmin.platform-tax-rates.index')->with('status', 'Tax rate updated.');
    }

    public function destroy(PlatformTaxRate $platformTaxRate): RedirectResponse
    {
        $platformTaxRate->delete();

        return redirect()->route('superadmin.platform-tax-rates.index')->with('status', 'Tax rate deleted.');
    }

    /**
     * Every local tax rate row needs a real Stripe Tax Rate object behind
     * it — Business::taxRates() attaches this to subscriptions, and
     * PlatformTaxCalculator uses the local row purely for display/
     * one-time-charge math. A row with a local percentage but no Stripe
     * object would show correctly here while silently charging $0 extra
     * tax on the actual subscription, so this runs on every save rather
     * than being a manual, easy-to-forget extra step. A Stripe hiccup
     * here must not block saving the local row — the admin can retry by
     * saving again, since this only fires when stripe_tax_rate_id is
     * still empty.
     */
    private function syncStripeTaxRate(PlatformTaxRate $rate): void
    {
        if ($rate->stripe_tax_rate_id) {
            return;
        }

        $description = $rate->province
            ? ($rate->province.' '.$rate->tax_label.' — Quote Builder subscription billing')
            : ($rate->country_code.'-wide '.$rate->tax_label.' — Quote Builder subscription billing');

        try {
            $stripeTaxRate = Cashier::stripe()->taxRates->create([
                'display_name' => $rate->tax_label,
                'description' => $description,
                'percentage' => (float) $rate->rate,
                'inclusive' => false,
                'country' => $rate->country_code,
                'state' => $rate->province,
            ]);

            $rate->update(['stripe_tax_rate_id' => $stripeTaxRate->id]);
        } catch (\Throwable $e) {
            Log::warning('Could not create Stripe Tax Rate for platform tax rate.', [
                'platform_tax_rate_id' => $rate->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'province' => ['nullable', 'string', 'max:60'],
            'tax_label' => ['required', 'string', 'max:20'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['country_code'] = strtoupper($validated['country_code']);

        // Normalize to the 2-letter code whenever it's recognized (e.g.
        // "Ontario" -> "ON"), so PlatformTaxCalculator's lookup always
        // matches regardless of how it was typed here. An unrecognized
        // value is kept as-is rather than silently dropped, so the Super
        // Admin sees exactly what they typed and can correct it.
        if ($validated['province']) {
            $validated['province'] = ProvinceCodes::normalize($validated['province']) ?? $validated['province'];
        } else {
            $validated['province'] = null;
        }

        return $validated;
    }
}
