<?php

namespace App\Http\Controllers;

use App\Mail\QuoteSubmittedToCustomer;
use App\Models\Business;
use App\Models\Product;
use App\Models\Quote;
use App\Services\CustomerResolver;
use App\Services\DiscountCalculator;
use App\Services\QuoteAnswerProcessor;
use App\Services\TaxCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The internal, authenticated counterpart to PublicQuoteController — staff
 * quoting a customer over the phone or in person, instead of the customer
 * filling out the public wizard themselves. Deliberately a *separate*
 * entry point, not a replacement: same RulesEngine calculation, same
 * published_snapshot data, same QuoteAnswerProcessor pipeline as the
 * public flow (see that class's docblock), but:
 *   - authenticated + product-access-scoped instead of public
 *   - never limited by Business::hasFeature() plan gates — see persist()
 *   - lets staff attach an existing customer (from past quotes) or a new
 *     one, add staff-only internal notes, and override the calculated
 *     price with an audit trail (Quote::calculated_price vs final_price)
 *
 * Nothing is written to the database until staff explicitly choose to —
 * finishing the wizard only calculates and shows the price (review()).
 * From there, Save/Email/Download PDF each independently persist the
 * quote (if not already saved) and then do their one extra thing; none
 * of the three require the others, so staff can e.g. email a quote
 * without ever downloading a PDF of it, or vice versa.
 *
 * Gated by hasFeature('quote_inbox') — same prerequisite as the inbox
 * itself, since a business with no inbox has nowhere to see the result —
 * and by Auth::user()->canAccessProduct(), same product-scoping every
 * other product-related screen already enforces for Members.
 *
 * Future internal-tooling extension point (see Prompt/requirement 9):
 * this is a natural home for things like a staff quote-approval workflow
 * (an extra status between 'New' and quotable), per-staff quote volume
 * reporting (via Quote::createdBy()), or a "duplicate this quote" action
 * for repeat customers — none of that exists yet, but the source/
 * created_by/calculated_price columns already added here are exactly
 * what such features would build on.
 */
class InternalQuoteController extends Controller
{
    /**
     * Product picker — the internal equivalent of PublicQuoteController's
     * picker(), but scoped to whichever products this staff member can
     * actually access (see Business::productsAccessibleTo()).
     */
    public function create(): View
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);

        $products = $business->productsAccessibleTo(Auth::user())
            ->where('is_active', true)
            ->whereNotNull('published_snapshot')
            ->orderBy('name')
            ->get();

        return view('quotes.create', compact('products'));
    }

    public function show(Product $product): View
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        return view('quotes.builder', [
            'business' => $business,
            'product' => $product,
            'snapshot' => $product->published_snapshot,
        ]);
    }

    /**
     * The end of the wizard — the *only* thing needed to get here is the
     * product's questions answered. Shows the price immediately, with
     * nothing written to the database yet; customer name/email, notes,
     * and the price override are all collected on this same review
     * screen, not before it — see review()'s docblock on the shared
     * business-side Blade partial for why (staff need to see the price
     * before deciding whether to override it).
     */
    public function review(Request $request, Product $product): View
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        $priced = $this->calculatePrice($request, $business, $product);

        return view('quotes.review', [
            'business' => $business,
            'product' => $product,
            'answersJson' => $priced['answersJson'],
            'result' => $priced['result'],
            'tax' => $priced['tax'],
            'calculatedPrice' => $priced['calculatedPrice'],
            'selections' => (new QuoteAnswerProcessor)->buildSelections($product->published_snapshot, $priced['answers']),
            'existingCustomers' => $this->recentCustomers($business),
            'preparedByName' => Auth::user()->name,
            'preparedByEmail' => Auth::user()->email,
        ]);
    }

    /**
     * The wizard's running-total display — same calculatePrice() pipeline
     * as review(), just returned as JSON instead of a page, so the price
     * on screen updates as staff answer each question rather than only
     * appearing once at the end. Never writes anything; purely read-only.
     */
    public function livePrice(Request $request, Product $product): JsonResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        $priced = $this->calculatePrice($request, $business, $product);

        return response()->json(['price' => $priced['calculatedPrice']]);
    }

    public function save(Request $request, Product $product): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        return redirect()->route('quotes.create.result', $quote)->with('status', 'Quote saved.');
    }

    public function email(Request $request, Product $product): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        $this->sendCustomerEmail($quote);

        return redirect()->route('quotes.create.result', $quote)->with('status', 'Quote saved and emailed to the customer.');
    }

    public function pdf(Request $request, Product $product): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        return redirect()->route('quote.pdf', $quote);
    }

    /**
     * Dedicated internal result page (not PublicQuoteController's
     * customer-facing one) — shows the calculated-vs-actual price
     * comparison and internal notes, neither of which a customer should
     * ever see. Reachable both right after save()/email()/pdf() and later
     * from the Quote Inbox, so it also offers Email/PDF on an
     * already-saved quote (email() may not have been used yet).
     */
    public function result(Quote $quote): View
    {
        abort_unless($quote->isInternal(), 404);
        abort_unless(Auth::user()->canAccessProduct($quote->product), 404);

        return view('quotes.result', [
            'quote' => $quote,
            'product' => $quote->product,
        ]);
    }

    /**
     * Emails an already-saved quote — reachable both from the "just
     * created" result page (internal quotes only ever land there first)
     * and from the general quotes.show detail page (any source). Public-
     * sourced quotes stay gated by the business's own email_notifications
     * feature, same as everywhere else that isn't an internal quote;
     * internal quotes bypass that gate as usual. Safe to call more than
     * once; each call just resends and bumps emailed_at.
     */
    public function emailExisting(Quote $quote): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($quote->product), 404);
        abort_unless($quote->isInternal() || $quote->business->hasFeature('email_notifications'), 404);

        $this->sendCustomerEmail($quote);

        $route = $quote->isInternal() ? 'quotes.create.result' : 'quotes.show';

        return redirect()->route($route, $quote)->with('status', 'Emailed to the customer.');
    }

    /**
     * Price-only calculation for review() — the wizard hands over just
     * the answers, nothing else, so that's all this validates. Re-run
     * fresh (never trusting anything from a prior step) same as
     * calculate() below.
     */
    private function calculatePrice(Request $request, Business $business, Product $product): array
    {
        $validated = $request->validate([
            'answers' => ['nullable', 'string'],
        ]);

        $snapshot = $product->published_snapshot;
        $rawAnswers = json_decode($validated['answers'] ?? '', true) ?: [];

        $processor = new QuoteAnswerProcessor;
        $answers = $processor->sanitizeAnswers($snapshot, $rawAnswers);
        $result = $processor->calculate($snapshot, $answers);
        $tax = (new TaxCalculator)->calculate($business, $result['final_price']);

        return [
            'answersJson' => $validated['answers'] ?? '',
            'answers' => $answers,
            'result' => $result,
            'tax' => $tax,
            'calculatedPrice' => $tax['total'],
        ];
    }

    /**
     * Validates the review screen's submitted fields (customer info,
     * notes, discount, override, and the answers carried through from
     * review() as a hidden field) and runs the RulesEngine + discount +
     * tax calculation again — no database write yet. Called fresh by each
     * of save()/email()/pdf() rather than trusting anything carried over
     * from review(), same "never trust the client" rule the public flow
     * already follows.
     */
    private function calculate(Request $request, Business $business, Product $product): array
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'answers' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(DiscountCalculator::TYPES)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'expires_in_days' => ['nullable', Rule::in(Quote::EXPIRATION_DAY_OPTIONS)],
            'prepared_by_name' => ['required', 'string', 'max:255'],
            'prepared_by_email' => ['required', 'email', 'max:255'],
        ]);

        $snapshot = $product->published_snapshot;
        $rawAnswers = json_decode($validated['answers'] ?? '', true) ?: [];

        $processor = new QuoteAnswerProcessor;
        $answers = $processor->sanitizeAnswers($snapshot, $rawAnswers);
        $result = $processor->calculate($snapshot, $answers);

        // Discount comes off the pre-tax subtotal — not the tax-inclusive
        // total — so tax is correctly computed on what the customer is
        // actually being charged, same as any real invoice.
        $discount = (new DiscountCalculator)->apply($result['final_price'], $validated['discount_type'] ?? null, $validated['discount_value'] ?? null);

        // Same tax treatment as the public flow — an internal quote should
        // still reflect what the customer actually owes, discount/override
        // or not.
        $tax = (new TaxCalculator)->calculate($business, $result['final_price'] - $discount['amount']);
        $calculatedPrice = $tax['total'];

        return [
            'validated' => $validated,
            'answers' => $answers,
            'result' => $result,
            'discount' => $discount,
            'tax' => $tax,
            'calculatedPrice' => $calculatedPrice,
            'finalPrice' => $validated['price_override'] ?? $calculatedPrice,
        ];
    }

    /**
     * Writes the Quote row — deliberately never limited by
     * Business::hasFeature() plan gates, same "staff always can" rule
     * covering PDF download for internal quotes (see downloadPdf()'s
     * comment in PublicQuoteController).
     */
    private function persist(Business $business, Product $product, array $bundle): Quote
    {
        $validated = $bundle['validated'];

        $customer = (new CustomerResolver)->findOrCreate($business, [
            'name' => $validated['customer_name'],
            'email' => $validated['customer_email'],
            'phone' => $validated['customer_phone'] ?? null,
        ]);

        $quote = Quote::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_id' => $customer->id,
            'final_price' => $bundle['finalPrice'],
            'calculated_price' => $bundle['calculatedPrice'],
            'source' => Quote::SOURCE_INTERNAL,
            'created_by' => Auth::id(),
            'internal_notes' => $validated['internal_notes'] ?? null,
            'expires_at' => isset($validated['expires_in_days']) ? now()->addDays((int) $validated['expires_in_days']) : null,
            'prepared_by_name' => $validated['prepared_by_name'],
            'prepared_by_email' => $validated['prepared_by_email'],
            'meta' => [
                'applied_rules' => $bundle['result']['applied_rules'],
                'base_price' => $product->published_snapshot['product']['base_price'] ?? 0,
                'discount' => $bundle['discount']['meta'],
                'subtotal_before_tax' => $bundle['tax']['subtotal'],
                'tax_lines' => $bundle['tax']['tax_lines'],
                'tax_total' => $bundle['tax']['tax_total'],
                'selections' => (new QuoteAnswerProcessor)->buildSelections($product->published_snapshot, $bundle['answers']),
                'customer_contact' => (new CustomerResolver)->snapshotContact($customer),
            ],
        ]);

        (new QuoteAnswerProcessor)->recordAnswers($product, $quote, $product->published_snapshot, $bundle['answers']);

        return $quote;
    }

    /**
     * Same customer-facing Mailable the public wizard sends — an internal
     * quote's customer shouldn't get a visibly different email just
     * because staff typed it in for them. Never gated by
     * hasFeature('email_notifications'): internal quotes bypass plan
     * gates everywhere else, and this is the one place staff explicitly
     * asked for the email to go out, so there's nothing to gate.
     */
    private function sendCustomerEmail(Quote $quote): void
    {
        $result = ['applied_rules' => $quote->meta['applied_rules'] ?? []];

        // The "view again later" link is still gated by the business's
        // own quote_customer_link feature — that route (quote.view) 404s
        // without it regardless of source, so a business without the
        // feature must not get a dead link mailed to their customer.
        $viewUrl = $quote->business->hasFeature('quote_customer_link')
            ? route('quote.view', $quote->uuid)
            : null;

        Mail::to($quote->customer_email)->send(new QuoteSubmittedToCustomer($quote, $result, $viewUrl));

        $quote->update(['emailed_at' => now()]);
    }

    /**
     * This business's saved customer directory (see App\Models\Customer),
     * most recently updated first — fuels the "pick an existing customer"
     * search on the builder page. Capped so a business with a long
     * customer history doesn't ship an ever-growing payload to the
     * browser; the search box filters client-side over whatever's here.
     */
    private function recentCustomers(Business $business): array
    {
        return $business->customers()
            ->latest('updated_at')
            ->limit(500)
            ->get(['id', 'name', 'email', 'phone'])
            ->all();
    }

    private function assertProductQuotable(Product $product): void
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);
        abort_unless($product->is_active, 404);
        abort_unless($product->published_snapshot, 404);
    }
}
