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
     * Re-opens an existing quote in the same wizard used to create one,
     * pre-filled with its previously-recorded answers — see quoteWizard()'s
     * initialAnswers param. What happens next depends on whether this
     * quote has already been emailed (see save()'s docblock): editing one
     * that hasn't updates it in place; editing one that has creates a
     * linked revision instead, leaving the original untouched.
     */
    public function edit(Quote $quote): View
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        abort_unless($quote->product && Auth::user()->canAccessProduct($quote->product), 404);
        $this->assertProductQuotable($quote->product);

        $initialAnswers = $quote->quoteAnswers->mapWithKeys(
            fn ($answer) => [(string) $answer->question_id => $answer->option_id ?? $answer->answer_value]
        )->all();

        return view('quotes.builder', [
            'business' => $business,
            'product' => $quote->product,
            'snapshot' => $quote->product->published_snapshot,
            'initialAnswers' => $initialAnswers,
            'editingQuoteId' => $quote->id,
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

        // Tenant-scoped via Quote's own BelongsToBusiness — a tampered
        // editing_quote_id for another business's quote just resolves to
        // null here, same as any other cross-tenant route-model lookup.
        $editingQuote = $request->filled('editing_quote_id')
            ? Quote::find($request->integer('editing_quote_id'))
            : null;

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
            'preparedByName' => $editingQuote->prepared_by_name ?? Auth::user()->name,
            'preparedByEmail' => $editingQuote->prepared_by_email ?? Auth::user()->email,
            'editingQuote' => $editingQuote,
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

        if ($limitResponse = $this->blockIfOverQuoteLimit($request, $business, $product)) {
            return $limitResponse;
        }

        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        return redirect()->route('quotes.create.result', $quote)->with('status', 'Quote saved.');
    }

    public function email(Request $request, Product $product): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        if ($limitResponse = $this->blockIfOverQuoteLimit($request, $business, $product)) {
            return $limitResponse;
        }

        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        $status = $this->trySendCustomerEmail($quote)
            ? 'Quote saved and emailed to the customer.'
            : 'Quote saved, but the email failed to send — check your mail settings and try again from the quote.';

        return redirect()->route('quotes.create.result', $quote)->with('status', $status);
    }

    public function pdf(Request $request, Product $product): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasFeature('quote_inbox'), 404);
        $this->assertProductQuotable($product);

        if ($limitResponse = $this->blockIfOverQuoteLimit($request, $business, $product)) {
            return $limitResponse;
        }

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
        abort_unless($this->canAccessQuote($quote), 404);

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
        abort_unless($this->canAccessQuote($quote), 404);
        abort_unless($quote->isInternal() || $quote->business->hasFeature('email_notifications'), 404);

        $sent = $this->trySendCustomerEmail($quote);

        $route = $quote->isInternal() ? 'quotes.create.result' : 'quotes.show';

        return redirect()->route($route, $quote)->with('status', $sent
            ? 'Emailed to the customer.'
            : 'The email failed to send — check your mail settings and try again.');
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
            'editing_quote_id' => ['nullable', 'integer'],
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
     *
     * Three outcomes, depending on $bundle['validated']['editing_quote_id']
     * (see edit()'s docblock):
     *   - not editing anything: an ordinary brand-new quote, new reference
     *     number.
     *   - editing a quote that was never emailed: updated in place — same
     *     id, same reference number, its old answers replaced with the
     *     new ones. Nothing was sent yet, so there's nothing to preserve.
     *   - editing a quote that WAS already emailed: a new quote is created
     *     instead, with its own new reference number and
     *     revises_quote_id pointing at the original. The original is
     *     never touched — whoever already has a copy of it keeps seeing
     *     exactly what they were sent.
     */
    private function persist(Business $business, Product $product, array $bundle): Quote
    {
        $validated = $bundle['validated'];

        $customer = (new CustomerResolver)->findOrCreate($business, [
            'name' => $validated['customer_name'],
            'email' => $validated['customer_email'],
            'phone' => $validated['customer_phone'] ?? null,
        ]);

        $editingQuote = ! empty($validated['editing_quote_id'])
            ? Quote::find($validated['editing_quote_id'])
            : null;

        $attributes = [
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
        ];

        if ($editingQuote && ! $editingQuote->emailed_at) {
            $editingQuote->update($attributes);
            $editingQuote->quoteAnswers()->delete();
            (new QuoteAnswerProcessor)->recordAnswers($product, $editingQuote, $product->published_snapshot, $bundle['answers']);

            return $editingQuote;
        }

        $attributes['reference_number'] = $business->nextQuoteReferenceNumber();

        if ($editingQuote) {
            $attributes['revises_quote_id'] = $editingQuote->id;
        }

        $quote = Quote::create($attributes);

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
     *
     * Unlike the public wizard's own notification emails (which are a
     * background side-effect of saving a quote), staff explicitly clicked
     * "Email" here — so a mail failure is worth telling them about
     * (returns false) rather than hiding it, but it must still never
     * crash the request: the quote itself is already safely saved by the
     * time this runs, and a mail server problem must not take that
     * successful save down with it.
     */
    private function trySendCustomerEmail(Quote $quote): bool
    {
        $result = ['applied_rules' => $quote->meta['applied_rules'] ?? []];

        // The "view again later" link is still gated by the business's
        // own quote_customer_link feature — that route (quote.view) 404s
        // without it regardless of source, so a business without the
        // feature must not get a dead link mailed to their customer.
        $viewUrl = $quote->business->hasFeature('quote_customer_link')
            ? route('quote.view', $quote->uuid)
            : null;

        try {
            Mail::to($quote->customer_email)->send(new QuoteSubmittedToCustomer($quote, $result, $viewUrl));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        $quote->update(['emailed_at' => now()]);

        return true;
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

    /**
     * canAccessProduct() needs a real Product — can't be called at all
     * once a quote's product has been deleted (product_id is null). No
     * per-product grant is left to check at that point (a Member's
     * access was always scoped to specific products, and that product is
     * gone), so only the Owner — who already sees everything regardless
     * — can still open it.
     */
    private function canAccessQuote(Quote $quote): bool
    {
        return $quote->product
            ? Auth::user()->canAccessProduct($quote->product)
            : Auth::user()->isOwner();
    }

    /**
     * The same monthly cap PublicQuoteController::store() already enforces
     * for customer-submitted quotes — reused here so staff-created quotes
     * count against (and are stopped by) the exact same shared total,
     * rather than only customers ever being the ones who hit a wall.
     * Never blocks editing an already-saved quote (editing_quote_id
     * present) — that isn't a new quote, so it shouldn't cost against the
     * limit or be refused because of it. Templates are exempt, same as
     * the public side, so Super Admin's own testing is never affected.
     */
    private function blockIfOverQuoteLimit(Request $request, Business $business, Product $product): ?RedirectResponse
    {
        if ($request->filled('editing_quote_id') || $business->is_template || ! $business->hasReachedMonthlyQuoteLimit()) {
            return null;
        }

        return redirect()->route('quotes.create.show', $product)
            ->with('error', "You've reached this month's quote limit — upgrade your plan to create more.");
    }
}
