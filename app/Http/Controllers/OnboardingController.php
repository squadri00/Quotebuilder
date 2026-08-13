<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Industry;
use App\Services\TemplateCloner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The post-login "pick your industry and starting products" flow that
 * used to live on the registration form itself — moved here because it
 * was confusing customers before they'd even paid. Reached via the
 * dismissible "Get Started" prompt on the Dashboard (see
 * DashboardController) or directly at /get-started any time after that.
 */
class OnboardingController extends Controller
{
    public function create(): View
    {
        $business = Auth::user()->business;

        $templates = Business::withoutGlobalScopes()
            ->withCount(['products' => fn ($query) => $query->withoutGlobalScopes()])
            ->with(['products' => fn ($query) => $query->withoutGlobalScopes()->where('is_active', true)->orderBy('name')])
            ->where('is_template', true)
            ->orderBy('name')
            ->get();
        $industries = Industry::orderBy('name')->get();

        return view('onboarding.create', compact('business', 'templates', 'industries'));
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
            'onboarding_dismissed_at' => now(),
        ]);

        $templateIds = $cloner->cloneProductsInto($business, $products);
        $business->templatesUsed()->attach($templateIds);

        return redirect()->route('dashboard')->with('status', $products->isEmpty()
            ? "You're all set — build your first product whenever you're ready."
            : "You're all set — {$products->count()} starting product(s) were added to your account.");
    }

    public function dismiss(): RedirectResponse
    {
        Auth::user()->business->update(['onboarding_dismissed_at' => now()]);

        return redirect()->route('dashboard');
    }
}
