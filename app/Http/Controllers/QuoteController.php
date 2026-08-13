<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The business's own view of quotes submitted for their products. Gated
 * server-side by Business::hasFeature('quote_inbox') — a business without
 * that feature gets a 404 here even if they guess the URL, not just a
 * hidden nav link. Status updates are separately gated by
 * quote_status_tracking, since a business could have the inbox without
 * status tracking.
 */
class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);

        $statusTrackingEnabled = $business->hasFeature('quote_status_tracking');

        // BelongsToBusiness's global scope already restricts this to the
        // logged-in business, but Quote also links back to Product, which
        // could theoretically be missing if a product itself was force-
        // deleted — eager load defensively. A Member additionally only
        // sees quotes for products they've been granted.
        $accessibleProductIds = $business->productsAccessibleTo(Auth::user())->pluck('id');

        $search = trim((string) $request->query('search', ''));
        $source = $request->query('source', '');
        $status = $request->query('status', '');
        $productId = $request->query('product', '');
        $sort = $request->query('sort', 'newest');

        $quotes = Quote::with(['product', 'createdBy'])
            ->whereIn('product_id', $accessibleProductIds)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($source, Quote::SOURCES, true), fn ($query) => $query->where('source', $source))
            ->when($statusTrackingEnabled && in_array($status, Quote::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($productId !== '' && in_array((int) $productId, $accessibleProductIds->all(), true), fn ($query) => $query->where('product_id', $productId))
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when($sort === 'price_high', fn ($query) => $query->orderByDesc('final_price'))
            ->when($sort === 'price_low', fn ($query) => $query->orderBy('final_price'))
            ->when($sort === 'newest' || ! in_array($sort, ['oldest', 'price_high', 'price_low'], true), fn ($query) => $query->latest())
            ->paginate(25)
            ->withQueryString();

        return view('quotes.index', [
            'quotes' => $quotes,
            'statusTrackingEnabled' => $statusTrackingEnabled,
            'products' => $business->productsAccessibleTo(Auth::user())->orderBy('name')->get(['id', 'name']),
            'filters' => compact('search', 'source', 'status', 'productId', 'sort'),
        ]);
    }

    /**
     * Full detail for one past quote, regardless of source — the "keep
     * track of this business's quotes" screen: selections, pricing/tax
     * breakdown, and an Email/PDF pair, same information already shown
     * right after creating an internal quote (see InternalQuoteController's
     * result() page) but reachable for any quote already sitting in the
     * inbox, public-submitted ones included.
     */
    public function show(Quote $quote): View
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        abort_unless(Auth::user()->canAccessProduct($quote->product), 404);

        return view('quotes.show', [
            'quote' => $quote,
            'product' => $quote->product,
            'pdfEnabled' => $quote->isInternal() || $business->hasFeature('pdf_download'),
            'emailEnabled' => $quote->isInternal() || $business->hasFeature('email_notifications'),
        ]);
    }

    public function updateStatus(Request $request, Quote $quote): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        abort_unless($business->hasFeature('quote_status_tracking'), 404);
        abort_unless(Auth::user()->canAccessProduct($quote->product), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Quote::STATUSES)],
        ]);

        $quote->update(['status' => $validated['status']]);

        return back()->with('status', 'Quote status updated.');
    }
}
