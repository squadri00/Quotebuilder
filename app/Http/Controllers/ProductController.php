<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\QuestionVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const MAX_IMAGES = 5;

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $business = Auth::user()->business;

        // Eager load everything hasUnpublishedChanges() needs per row in
        // the view, so listing products doesn't run N extra query sets.
        // A Member only sees products explicitly granted to them.
        $products = $business->productsAccessibleTo(Auth::user())
            ->with(['questions.options', 'rules'])->orderBy('name')->get();

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        abort_if(Auth::user()->isMember(), 404, 'Members cannot create new products.');

        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(Auth::user()->isMember(), 404, 'Members cannot create new products.');

        $business = Auth::user()->business;

        if ($business->hasReachedProductLimit()) {
            return redirect()->route('products.index')->with(
                'error',
                "You've reached your plan's limit of {$business->productLimit()} product(s). Upgrade your plan to add more."
            );
        }

        $validated = $this->validateProduct($request);

        Product::create($validated);

        return redirect()->route('products.index')->with('status', 'Product created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);

        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);

        $validated = $this->validateProduct($request);

        $product->update($validated);

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        abort_if(Auth::user()->isMember(), 404, 'Members cannot delete products.');

        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }

    /**
     * Freeze the product's current draft (itself + all its questions,
     * options, and rules) into published_snapshot — that snapshot, not
     * these live rows, is what the public quote builder reads from.
     */
    public function publish(Product $product): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);

        $product->loadMissing('questions.options');

        QuestionVisibility::assertPublishable($product->questions);

        $snapshot = $product->buildPublishableSnapshot();

        $product->update([
            'is_published' => true,
            'published_snapshot' => $snapshot,
            'published_at' => now(),
        ]);

        $questionIds = $product->questions->pluck('id');

        $product->questions()->update(['is_published' => true]);
        Option::whereIn('question_id', $questionIds)->update(['is_published' => true]);
        $product->rules()->update(['is_published' => true]);

        return redirect()->route('products.edit', $product)
            ->with('status', 'Product published — the public quote builder now reflects these changes.');
    }

    /**
     * Uploads shown to both the internal and public quote builders — see
     * Product::buildPublishableSnapshot() and images(). Deliberately a
     * separate endpoint from update(), same pattern as
     * BusinessSettingsController's split details/branding forms, so the
     * main product form never needs enctype="multipart/form-data".
     */
    public function storeImages(Request $request, Product $product): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);

        $existingCount = $product->images()->count();
        $remaining = max(0, self::MAX_IMAGES - $existingCount);

        if ($remaining === 0) {
            return back()->with('error', 'This product already has the maximum of '.self::MAX_IMAGES.' images — remove one before adding another.');
        }

        $validated = $request->validate([
            'images' => ['required', 'array', 'max:'.$remaining],
            'images.*' => ['image', 'max:4096'],
        ]);

        $nextSortOrder = ($product->images()->max('sort_order') ?? -1) + 1;

        foreach ($validated['images'] as $file) {
            ProductImage::create([
                'business_id' => $product->business_id,
                'product_id' => $product->id,
                'path' => $file->store('product-images', 'public'),
                'sort_order' => $nextSortOrder++,
            ]);
        }

        return redirect()->route('products.edit', $product)
            ->with('status', 'Image(s) uploaded — publish to make them visible to customers and staff.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()->route('products.edit', $product)
            ->with('status', 'Image removed — publish to update the customer-facing pages.');
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
