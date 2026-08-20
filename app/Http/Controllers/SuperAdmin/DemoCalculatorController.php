<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\DemoCalculator;
use App\Support\DemoCalculatorIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DemoCalculatorController extends Controller
{
    public function index(): View
    {
        $demoCalculators = DemoCalculator::with('business')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('superadmin.demo-calculators.index', compact('demoCalculators'));
    }

    public function create(): View
    {
        return view('superadmin.demo-calculators.create', [
            'businesses' => $this->eligibleBusinesses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $demoCalculator = DemoCalculator::create([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.demo-calculators.index')->with('status', "\"{$demoCalculator->name}\" added.");
    }

    public function edit(DemoCalculator $demoCalculator): View
    {
        return view('superadmin.demo-calculators.edit', [
            'demoCalculator' => $demoCalculator,
            'businesses' => $this->eligibleBusinesses(),
        ]);
    }

    public function update(Request $request, DemoCalculator $demoCalculator): RedirectResponse
    {
        $validated = $this->validated($request);

        $demoCalculator->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.demo-calculators.index')->with('status', "\"{$demoCalculator->name}\" updated.");
    }

    public function toggleActive(DemoCalculator $demoCalculator): RedirectResponse
    {
        $demoCalculator->update(['is_active' => ! $demoCalculator->is_active]);

        return back()->with('status', "\"{$demoCalculator->name}\" is now ".($demoCalculator->is_active ? 'live' : 'hidden').' on the Demo page.');
    }

    public function destroy(DemoCalculator $demoCalculator): RedirectResponse
    {
        $name = $demoCalculator->name;

        $demoCalculator->delete();

        return redirect()->route('superadmin.demo-calculators.index')->with('status', "\"{$name}\" deleted.");
    }

    /**
     * A demo card only makes sense pointing at one of our own template
     * businesses (see Business::is_template — the same catalogs
     * TemplateCloner uses at signup), never a real customer account.
     */
    private function eligibleBusinesses()
    {
        return Business::withoutGlobalScopes()
            ->where('is_template', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'business_id' => [
                'required',
                Rule::exists((new Business)->getTable(), 'id')->where('is_template', true),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'icon' => ['required', 'string', Rule::in(DemoCalculatorIcons::paths())],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 999;

        return $validated;
    }
}
