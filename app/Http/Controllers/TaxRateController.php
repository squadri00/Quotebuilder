<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    public function index(): View
    {
        $taxRates = TaxRate::orderBy('id')->get();

        return view('tax-rates.index', compact('taxRates'));
    }

    public function create(): View
    {
        return view('tax-rates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTaxRate($request);

        TaxRate::create($validated);

        return redirect()->route('tax-rates.index')->with('status', 'Tax line added.');
    }

    public function edit(TaxRate $taxRate): View
    {
        return view('tax-rates.edit', compact('taxRate'));
    }

    public function update(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $validated = $this->validateTaxRate($request);

        $taxRate->update($validated);

        return redirect()->route('tax-rates.index')->with('status', 'Tax line updated.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return redirect()->route('tax-rates.index')->with('status', 'Tax line deleted.');
    }

    private function validateTaxRate(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
