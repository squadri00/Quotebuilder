<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Option;
use App\Models\Product;
use App\Support\QuestionVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Super Admin's own native quote builder — lets platform staff build
 * and edit a business's products (templates and real customer accounts
 * alike) directly, without impersonating a hidden user. Deliberately
 * mirrors the business-side ProductController/QuestionController/
 * OptionController/RuleController one-for-one (same validation, same
 * RulesEngine-compatible data shapes, same publish snapshot mechanism)
 * so a product built here behaves identically to one a business owner
 * built themselves — just reached via /superadmin/ on the admin guard
 * instead of product/question/option access-control checks that only
 * make sense for a logged-in business user (canAccessProduct(), plan
 * limits) are intentionally skipped here: Super Admin can always act on
 * any business's catalog.
 */
class ProductController extends Controller
{
    public function index(Business $business): View
    {
        // Every nested relation needs its own withoutGlobalScopes() — see
        // TemplateCloner::clone()'s docblock for why. Without this, the
        // product list (and each product's questions/options/rules)
        // silently filters to whatever business a 'web' guard session
        // happens to be logged into elsewhere in this same browser
        // session, which reads as "the template lost its products" when
        // nothing was actually lost.
        $products = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->with([
                'questions' => fn ($query) => $query->withoutGlobalScopes(),
                'questions.options' => fn ($query) => $query->withoutGlobalScopes(),
                'rules' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->orderBy('name')
            ->get();

        return view('superadmin.products.index', compact('business', 'products'));
    }

    public function create(Business $business): View
    {
        return view('superadmin.products.create', compact('business'));
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $validated['business_id'] = $business->id;

        $product = Product::create($validated);

        AuditLog::record(
            $request->user('admin'),
            'product.created',
            $business,
            "Created product \"{$product->name}\" (#{$product->id}) for \"{$business->name}\" (#{$business->id})."
        );

        return redirect()->route('superadmin.products.questions.index', [$business, $product])
            ->with('status', "\"{$product->name}\" created — add its questions below.");
    }

    public function edit(Business $business, Product $product): View
    {
        abort_unless($product->business_id === $business->id, 404);

        return view('superadmin.products.edit', compact('business', 'product'));
    }

    public function update(Request $request, Business $business, Product $product): RedirectResponse
    {
        abort_unless($product->business_id === $business->id, 404);

        $validated = $this->validateProduct($request);

        $product->update($validated);

        AuditLog::record(
            $request->user('admin'),
            'product.updated',
            $business,
            "Updated product \"{$product->name}\" (#{$product->id})."
        );

        return redirect()->route('superadmin.products.index', $business)->with('status', 'Product updated.');
    }

    public function destroy(Request $request, Business $business, Product $product): RedirectResponse
    {
        abort_unless($product->business_id === $business->id, 404);

        $name = $product->name;

        AuditLog::record(
            $request->user('admin'),
            'product.deleted',
            $business,
            "Deleted product \"{$name}\" (#{$product->id})."
        );

        $product->delete();

        return redirect()->route('superadmin.products.index', $business)->with('status', "\"{$name}\" deleted.");
    }

    /**
     * Freezes the product's current draft into published_snapshot —
     * identical mechanics to the business-side ProductController@publish,
     * so a template or customer product built here is consumable by the
     * exact same public/internal quote builders either way.
     */
    public function publish(Request $request, Business $business, Product $product): RedirectResponse
    {
        abort_unless($product->business_id === $business->id, 404);

        $product->loadMissing('questions.options');

        QuestionVisibility::assertPublishable($product->questions);

        $snapshot = $product->buildPublishableSnapshot();

        Product::withoutPublishTracking(fn () => $product->update([
            'is_published' => true,
            'published_snapshot' => $snapshot,
            'published_at' => now(),
        ]));

        $questionIds = $product->questions->pluck('id');

        $product->questions()->update(['is_published' => true]);
        Option::whereIn('question_id', $questionIds)->update(['is_published' => true]);
        $product->rules()->update(['is_published' => true]);

        AuditLog::record(
            $request->user('admin'),
            'product.published',
            $business,
            "Published product \"{$product->name}\" (#{$product->id})."
        );

        return redirect()->route('superadmin.products.edit', [$business, $product])
            ->with('status', 'Product published.');
    }

    /**
     * Shows the real public quote wizard (via an auto-resizing iframe —
     * same mechanism as the embeddable widget, see public/embed.js) still
     * wrapped in the Super Admin shell, so staff never have to leave the
     * admin panel to test a product.
     */
    public function preview(Business $business, Product $product): View
    {
        abort_unless($product->business_id === $business->id, 404);
        abort_unless($product->published_snapshot, 404);

        return view('superadmin.products.preview', compact('business', 'product'));
    }

    private function validateProduct(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
