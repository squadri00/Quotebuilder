<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeatureController extends Controller
{
    public function index(): View
    {
        $features = Feature::orderBy('name')->get();

        return view('superadmin.features.index', compact('features'));
    }

    public function create(): View
    {
        return view('superadmin.features.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $feature = Feature::create($validated);

        return redirect()->route('superadmin.features.index')->with('status', "Feature \"{$feature->name}\" created.");
    }

    public function edit(Feature $feature): View
    {
        return view('superadmin.features.edit', compact('feature'));
    }

    public function update(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $this->validated($request, $feature);

        $feature->update($validated);

        return redirect()->route('superadmin.features.index')->with('status', "Feature \"{$feature->name}\" updated.");
    }

    public function destroy(Feature $feature): RedirectResponse
    {
        $name = $feature->name;
        $feature->delete();

        return redirect()->route('superadmin.features.index')->with('status', "Feature \"{$name}\" deleted.");
    }

    private function validated(Request $request, ?Feature $feature = null): array
    {
        return $request->validate([
            'key' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('features', 'key')->ignore($feature),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
