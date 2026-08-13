<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformTaxRate;
use App\Support\ProvinceCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        PlatformTaxRate::create([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.platform-tax-rates.index')->with('status', 'Tax rate added.');
    }

    public function edit(PlatformTaxRate $platformTaxRate): View
    {
        return view('superadmin.platform-tax-rates.edit', ['rate' => $platformTaxRate]);
    }

    public function update(Request $request, PlatformTaxRate $platformTaxRate): RedirectResponse
    {
        $validated = $this->validated($request);

        $platformTaxRate->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.platform-tax-rates.index')->with('status', 'Tax rate updated.');
    }

    public function destroy(PlatformTaxRate $platformTaxRate): RedirectResponse
    {
        $platformTaxRate->delete();

        return redirect()->route('superadmin.platform-tax-rates.index')->with('status', 'Tax rate deleted.');
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
