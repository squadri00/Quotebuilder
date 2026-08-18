<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Industry;
use App\Services\SetupGuideService;
use App\Services\TemplateCloner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The full post-signup "Setup Guide" — starts with the original "pick your
 * industry and starting products" step (which used to live on the
 * registration form itself, moved here because it was confusing customers
 * before they'd even paid) and now walks through everything a business
 * needs before its first real quote works correctly: profile/branding, tax
 * rates, a published product, and team invites. Reached via the Dashboard
 * banner or the permanent "Setup Guide" link in the sidebar, any time.
 */
class OnboardingController extends Controller
{
    public function create(SetupGuideService $setupGuide): View
    {
        $business = Auth::user()->business;

        $templates = Business::withoutGlobalScopes()
            ->withCount(['products' => fn ($query) => $query->withoutGlobalScopes()])
            ->with(['products' => fn ($query) => $query->withoutGlobalScopes()->where('is_active', true)->orderBy('name')])
            ->where('is_template', true)
            ->orderBy('name')
            ->get();
        $industries = Industry::orderBy('name')->get();

        return view('onboarding.create', [
            'business' => $business,
            'templates' => $templates,
            'industries' => $industries,
            'steps' => $setupGuide->steps($business),
            'progress' => $setupGuide->progress($business),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        $business = Auth::user()->business;

        $cloner = new TemplateCloner;
        $products = $cloner->resolveProducts($request->input('product_ids', []));

        $business->update([
            'industry_id' => $request->industry_id,
        ]);

        $templateIds = $cloner->cloneProductsInto($business, $products);
        $business->templatesUsed()->attach($templateIds);

        return redirect()->route('onboarding.create')->with('status', $products->isEmpty()
            ? "Nice — now let's finish setting up your account below."
            : "{$products->count()} starting product(s) were added. Now let's finish setting up your account below.");
    }

    public function dismiss(): RedirectResponse
    {
        Auth::user()->business->update(['onboarding_dismissed_at' => now()]);

        return redirect()->route('dashboard');
    }
}
