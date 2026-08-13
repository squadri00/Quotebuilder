<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ImplementationTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImplementationTierController extends Controller
{
    public function index(): View
    {
        $tiers = ImplementationTier::withCount('orders')->orderBy('price')->get();

        return view('superadmin.implementation-tiers.index', compact('tiers'));
    }

    public function create(): View
    {
        return view('superadmin.implementation-tiers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $tier = ImplementationTier::create([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.implementation-tiers.index')->with('status', "\"{$tier->name}\" created.");
    }

    public function edit(ImplementationTier $implementationTier): View
    {
        return view('superadmin.implementation-tiers.edit', ['tier' => $implementationTier]);
    }

    public function update(Request $request, ImplementationTier $implementationTier): RedirectResponse
    {
        $validated = $this->validated($request);

        $implementationTier->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.implementation-tiers.index')->with('status', "\"{$implementationTier->name}\" updated.");
    }

    public function destroy(ImplementationTier $implementationTier): RedirectResponse
    {
        $name = $implementationTier->name;
        $implementationTier->delete();

        return redirect()->route('superadmin.implementation-tiers.index')->with('status', "\"{$name}\" deleted.");
    }

    public function toggleActive(ImplementationTier $implementationTier): RedirectResponse
    {
        $implementationTier->update(['is_active' => ! $implementationTier->is_active]);

        return back()->with('status', "\"{$implementationTier->name}\" is now ".($implementationTier->is_active ? 'active.' : 'deactivated.'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_count' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
