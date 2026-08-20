<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        $countries = Country::orderBy('name')->get();

        return view('superadmin.countries.index', compact('countries'));
    }

    public function create(): View
    {
        return view('superadmin.countries.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $country = Country::create([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.countries.index')->with('status', "\"{$country->name}\" added.");
    }

    public function edit(Country $country): View
    {
        return view('superadmin.countries.edit', compact('country'));
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $validated = $this->validated($request, $country);

        $country->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.countries.index')->with('status', "\"{$country->name}\" updated.");
    }

    public function toggleActive(Country $country): RedirectResponse
    {
        $country->update(['is_active' => ! $country->is_active]);

        return back()->with('status', "\"{$country->name}\" is now ".($country->is_active ? 'active' : 'inactive').'.');
    }

    /**
     * Businesses store their country as a plain name string (see
     * register-form.blade.php), never a foreign key to this table, so
     * deleting a country here can't orphan anything — a business already
     * registered from here keeps its own stored name untouched.
     */
    public function destroy(Country $country): RedirectResponse
    {
        $name = $country->name;

        $country->delete();

        return redirect()->route('superadmin.countries.index')->with('status', "\"{$name}\" deleted.");
    }

    private function validated(Request $request, ?Country $country = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_code' => [
                'required', 'string', 'size:2',
                Rule::unique('countries', 'short_code')->ignore($country),
            ],
            'currency_code' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'currency_position' => ['required', Rule::in(['before', 'after'])],
        ]);

        $validated['short_code'] = strtoupper($validated['short_code']);
        $validated['currency_code'] = strtoupper($validated['currency_code']);

        return $validated;
    }
}
