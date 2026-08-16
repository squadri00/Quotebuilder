<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\TemplateCloner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Self-service version of what Super Admin's "Copy Template" action does
 * for any business — available to every plan, any time, not just once at
 * onboarding (see OnboardingController for that one-time flow). A
 * business can't change its own Industry here (only Super Admin can,
 * per Business Management) — this only ever shows templates matching
 * whatever Industry is already assigned.
 */
class TemplateController extends Controller
{
    public function index(): View
    {
        $business = Auth::user()->business;

        $availableProducts = collect();

        if ($business->industry_id) {
            $availableProducts = Business::withoutGlobalScopes()
                ->where('is_template', true)
                ->where('industry_id', $business->industry_id)
                ->with(['products' => fn ($query) => $query->withoutGlobalScopes()
                    ->where('is_active', true)
                    ->orderBy('name')
                    // Without this, ->questions on each cross-tenant product
                    // lazy-loads under the viewing business's own tenant
                    // scope instead of the template's, silently returning 0
                    // regardless of how many questions the template actually has.
                    ->with(['questions' => fn ($q) => $q->withoutGlobalScopes()])])
                ->get()
                ->flatMap(fn ($template) => $template->products);
        }

        $myProducts = $business->products()->orderBy('name')->get();

        $installedTemplateProductIds = $myProducts->pluck('source_template_product_id')->filter()->all();

        return view('templates.index', compact('business', 'availableProducts', 'myProducts', 'installedTemplateProductIds'));
    }

    /**
     * $replace_product_id lets a business at its plan's product limit swap
     * one existing product out for a template one in a single action,
     * instead of removing it on its own first and coming back — see the
     * "Replace" picker in templates.index, shown only once they're
     * actually at the limit.
     */
    public function store(Request $request, int $product): RedirectResponse
    {
        abort_if(Auth::user()->isMember(), 404, 'Members cannot add products.');

        $request->validate([
            'replace_product_id' => ['nullable', 'integer'],
        ]);

        $business = Auth::user()->business;

        $cloner = new TemplateCloner;
        $resolved = $cloner->resolveProducts([$product]);

        // resolveProducts() already confirms this is a real, active
        // template product — the extra check here is the industry match,
        // which isn't that method's job (it's shared with onboarding,
        // which filters by industry client-side instead).
        $templateProduct = $resolved->first(fn ($p) => Business::withoutGlobalScopes()->find($p->business_id)?->industry_id === $business->industry_id
        );

        if (! $templateProduct) {
            return redirect()->route('templates.index')
                ->with('error', 'That product is not available for your industry.');
        }

        // Checked and resolved before anything is deleted, so a bad
        // request never costs someone their existing product for nothing.
        $toReplace = null;

        $alreadyInstalled = $business->products()
            ->where('source_template_product_id', $templateProduct->id)
            ->when($request->filled('replace_product_id'), fn ($query) => $query->whereKeyNot($request->integer('replace_product_id')))
            ->exists();

        if ($alreadyInstalled) {
            return redirect()->route('templates.index')
                ->with('error', "\"{$templateProduct->name}\" is already in your products.");
        }

        if ($business->hasReachedProductLimit()) {
            $toReplace = $request->filled('replace_product_id')
                ? $business->products()->find($request->integer('replace_product_id'))
                : null;

            if (! $toReplace) {
                return redirect()->route('templates.index')
                    ->with('error', "You've reached your plan's limit of {$business->productLimit()} product(s). Remove one to make room, or upgrade your plan.");
            }
        }

        DB::transaction(function () use ($cloner, $business, $templateProduct, $toReplace) {
            $toReplace?->delete();

            $templateIds = $cloner->cloneProductsInto($business, collect([$templateProduct]));
            $business->templatesUsed()->syncWithoutDetaching($templateIds);
        });

        $message = $toReplace
            ? "Replaced \"{$toReplace->name}\" with \"{$templateProduct->name}\"."
            : "\"{$templateProduct->name}\" was added to your products.";

        return redirect()->route('templates.index')->with('status', $message);
    }
}
