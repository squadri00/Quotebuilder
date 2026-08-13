<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::withCount(['features', 'businesses'])->orderBy('price')->get();

        return view('superadmin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        $features = Feature::orderBy('name')->get();

        return view('superadmin.plans.create', compact('features'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $plan = Plan::create([
            ...collect($validated)->except('features')->all(),
            'is_active' => $request->boolean('is_active'),
            'is_highlighted' => $request->boolean('is_highlighted'),
        ]);
        $plan->features()->sync($validated['features'] ?? []);

        return redirect()->route('superadmin.plans.index')->with('status', "Plan \"{$plan->name}\" created.");
    }

    public function edit(Plan $plan): View
    {
        $features = Feature::orderBy('name')->get();
        $selectedFeatureIds = $plan->features()->pluck('features.id')->all();

        return view('superadmin.plans.edit', compact('plan', 'features', 'selectedFeatureIds'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validated($request, $plan);

        $plan->update([
            ...collect($validated)->except('features')->all(),
            'is_active' => $request->boolean('is_active'),
            'is_highlighted' => $request->boolean('is_highlighted'),
        ]);
        $plan->features()->sync($validated['features'] ?? []);

        return redirect()->route('superadmin.plans.index')->with('status', "Plan \"{$plan->name}\" updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('superadmin.plans.index')->with('status', "Plan \"{$name}\" deleted.");
    }

    public function toggleActive(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('status', "\"{$plan->name}\" is now ".($plan->is_active ? 'active.' : 'deactivated.'));
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', Rule::in(['monthly', 'yearly'])],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_quotes_per_month' => ['nullable', 'integer', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'marketing_bullets' => ['nullable', 'string'],
            'is_highlighted' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['integer', Rule::exists('features', 'id')],
        ]);
    }
}
