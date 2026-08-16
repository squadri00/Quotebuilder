<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a business hand-pick which of its own products appear on its public
 * "Quote Hub" (PublicQuoteController::picker()) and in what order — see
 * that controller's docblock for what the hub itself is. Only ever
 * touches products.show_in_quote_hub / quote_hub_sort_order; managed from
 * a card on the Products index page rather than its own page.
 */
class QuoteHubController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_if(Auth::user()->isMember(), 404, 'Members cannot manage the Quote Hub.');

        $validated = $request->validate([
            'products' => ['array'],
            'products.*.show' => ['nullable', 'boolean'],
            'products.*.order' => ['nullable', 'integer', 'min:1'],
        ]);

        $entries = $validated['products'] ?? [];

        // Only ever acts on products that could actually appear on the
        // hub (active + published) — an inactive or unpublished product's
        // hub fields are left untouched, same as how it's excluded from
        // the management form itself, so there's nothing to smuggle in
        // via a hand-crafted request for a product that isn't shown here.
        $eligibleProducts = Auth::user()->business->products()
            ->where('is_active', true)
            ->whereNotNull('published_snapshot')
            ->get();

        foreach ($eligibleProducts as $product) {
            $show = (bool) ($entries[$product->id]['show'] ?? false);

            $product->update([
                'show_in_quote_hub' => $show,
                'quote_hub_sort_order' => $show ? ($entries[$product->id]['order'] ?? 999) : null,
            ]);
        }

        return redirect()->route('products.index')->with('status', 'Quote Hub updated.');
    }
}
