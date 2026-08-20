<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use App\Support\SocialPlatforms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SocialLinkController extends Controller
{
    public function index(): View
    {
        $socialLinks = SocialLink::orderBy('sort_order')->orderBy('id')->get();

        return view('superadmin.social-links.index', compact('socialLinks'));
    }

    public function create(): View
    {
        return view('superadmin.social-links.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $socialLink = SocialLink::create($this->validated($request));

        return redirect()->route('superadmin.social-links.index')->with('status', "\"{$socialLink->displayLabel()}\" added.");
    }

    public function edit(SocialLink $socialLink): View
    {
        return view('superadmin.social-links.edit', compact('socialLink'));
    }

    public function update(Request $request, SocialLink $socialLink): RedirectResponse
    {
        $socialLink->update($this->validated($request));

        return redirect()->route('superadmin.social-links.index')->with('status', "\"{$socialLink->displayLabel()}\" updated.");
    }

    public function destroy(SocialLink $socialLink): RedirectResponse
    {
        $label = $socialLink->displayLabel();
        $socialLink->delete();

        return redirect()->route('superadmin.social-links.index')->with('status', "\"{$label}\" deleted.");
    }

    public function toggleActive(SocialLink $socialLink): RedirectResponse
    {
        $socialLink->update(['is_active' => ! $socialLink->is_active]);

        return redirect()->route('superadmin.social-links.index')
            ->with('status', "\"{$socialLink->displayLabel()}\" is now " . ($socialLink->is_active ? 'active' : 'hidden') . '.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in(SocialPlatforms::keys())],
            'label' => ['nullable', 'string', 'max:50'],
            'url' => ['required', 'url', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        if ($validated['platform'] === 'other' && empty($validated['label'])) {
            $request->validate(['label' => ['required']], ['label.required' => 'A label is required when the platform is "Other".']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
