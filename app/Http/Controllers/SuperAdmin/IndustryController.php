<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IndustryController extends Controller
{
    public function index(): View
    {
        $industries = Industry::withCount('businesses')->with('templates')->orderBy('name')->get();

        return view('superadmin.industries.index', compact('industries'));
    }

    public function create(): View
    {
        return view('superadmin.industries.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $industry = Industry::create($validated);

        return redirect()->route('superadmin.industries.index')->with('status', "Industry \"{$industry->name}\" created.");
    }

    public function edit(Industry $industry): View
    {
        return view('superadmin.industries.edit', compact('industry'));
    }

    public function update(Request $request, Industry $industry): RedirectResponse
    {
        $validated = $this->validated($request, $industry);

        $industry->update($validated);

        return redirect()->route('superadmin.industries.index')->with('status', "Industry \"{$industry->name}\" updated.");
    }

    public function destroy(Industry $industry): RedirectResponse
    {
        $name = $industry->name;
        $industry->delete();

        return redirect()->route('superadmin.industries.index')->with('status', "Industry \"{$name}\" deleted.");
    }

    private function validated(Request $request, ?Industry $industry = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('industries', 'name')->ignore($industry),
            ],
        ]);
    }
}
