<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Super Admin's equivalent of QuoteHubController — lets an admin hand-pick
 * which of a given business's products appear on its public "Quote Hub"
 * (PublicQuoteController::picker()), for any business (works for templates
 * and real customer businesses alike), not just Auth::user()->business.
 * Only ever touches products.show_in_quote_hub / quote_hub_sort_order.
 */
class QuoteHubController extends Controller
{
    public function update(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'products' => ['array'],
            'products.*.show' => ['nullable', 'boolean'],
            'products.*.order' => ['nullable', 'integer', 'min:1'],
        ]);

        $entries = $validated['products'] ?? [];

        $eligibleProducts = $business->products()
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

        return redirect()->route('superadmin.products.index', $business)->with('status', 'Quote Hub updated.');
    }
}
