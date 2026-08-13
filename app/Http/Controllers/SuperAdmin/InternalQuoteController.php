<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
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
 * Super Admin's own testing/demo quote tool — deliberately NOT "quote any
 * real business's customer." Every product reachable here belongs to a
 * Template (Business::is_template), never a real customer's account, so a
 * demo quote can never land in — or even be reachable from — any real
 * business's Quote Inbox. Templates already have no real staff logged in
 * (only a placeholder "Template Admin" account with a password nobody
 * has), so ordinary multi-tenant scoping already guarantees that
 * isolation; nothing extra is needed once every quote here is pinned to
 * a template's business_id. See assertProductQuotable().
 *
 * Reusing the real templates (rather than a separate demo-only catalog)
 * is deliberate: Super Admin already builds and maintains real product
 * catalogs there via SuperAdmin\ProductController — a new quotable test
 * product belongs there too, not in a second parallel place.
 *
 * The save/email/PDF mechanics are otherwise real, on purpose — this
 * exists so Super Admin can genuinely test the flow (real DB row, real
 * email delivery, real PDF, real "view your quote" link), not a mockup.
 * See the business-side App\Http\Controllers\InternalQuoteController,
 * which this mirrors one-for-one for that part.
 *
 * The one thing genuinely unique to this controller: since the
 * "business" behind a demo quote is a template rather than a real
 * business, whoever is demoing it can type in a throwaway Business
 * Name/Address/Phone/Email per quote (see calculate()'s demo_business_*
 * fields) — stored on the Quote itself (meta.demo_business, via
 * Quote::demoBusinessOverride()) — so the resulting email/PDF/view can
 * look like it came from any fictional business, without that identity
 * ever being saved back to the template itself.
 */
class InternalQuoteController extends Controller
{
    /**
     * Every template, with how many quotable products each one has —
     * the first step of the demo flow: pick which template to demo.
     */
    public function create(): View
    {
        $templates = Business::where('is_template', true)
            ->withCount(['products' => function ($query) {
                $query->where('is_active', true)->whereNotNull('published_snapshot');
            }])
            ->with('industry')
            ->orderBy('name')
            ->get();

        return view('superadmin.quotes.templates', compact('templates'));
    }

    /**
     * Second step — this one template's quotable products.
     */
    public function products(Business $template): View
    {
        abort_unless($template->is_template, 404);

        $products = $template->products()
            ->where('is_active', true)
            ->whereNotNull('published_snapshot')
            ->orderBy('name')
            ->get();

        return view('superadmin.quotes.create', ['business' => $template, 'products' => $products]);
    }

    public function show(Product $product): View
    {
        $this->assertProductQuotable($product);

        return view('superadmin.quotes.builder', [
            'business' => $product->business,
            'product' => $product,
            'snapshot' => $product->published_snapshot,
        ]);
    }

    /**
     * The end of the wizard — the only thing needed to get here is the
     * product's questions answered. Shows the price immediately, with
     * nothing written to the database yet; customer name/email, notes,
     * price override, and the demo business identity are all collected
     * on this same review screen, not before it.
     */
    public function review(Request $request, Product $product): View
    {
        $this->assertProductQuotable($product);

        $business = $product->business;
        $priced = $this->calculatePrice($request, $business, $product);

        return view('superadmin.quotes.review', [
            'business' => $business,
            'product' => $product,
            'answersJson' => $priced['answersJson'],
            'result' => $priced['result'],
            'tax' => $priced['tax'],
            'calculatedPrice' => $priced['calculatedPrice'],
            'selections' => (new QuoteAnswerProcessor)->buildSelections($product->published_snapshot, $priced['answers']),
            'existingCustomers' => $this->recentCustomers($business),
            'preparedByName' => Auth::guard('admin')->user()->name,
            'preparedByEmail' => Auth::guard('admin')->user()->email,
        ]);
    }

    /**
     * The wizard's running-total display — same calculatePrice() pipeline
     * as review(), just returned as JSON instead of a page. See the
     * business-side InternalQuoteController::livePrice() this mirrors.
     */
    public function livePrice(Request $request, Product $product): JsonResponse
    {
        $this->assertProductQuotable($product);

        $priced = $this->calculatePrice($request, $product->business, $product);

        return response()->json(['price' => $priced['calculatedPrice']]);
    }

    public function save(Request $request, Product $product): RedirectResponse
    {
        $this->assertProductQuotable($product);

        $business = $product->business;
        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        return redirect()->route('superadmin.quotes.result', $quote)->with('status', 'Demo quote saved.');
    }

    public function email(Request $request, Product $product): RedirectResponse
    {
        $this->assertProductQuotable($product);

        $business = $product->business;
        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        $this->sendCustomerEmail($quote);

        return redirect()->route('superadmin.quotes.result', $quote)->with('status', 'Demo quote saved and emailed.');
    }

    public function pdf(Request $request, Product $product): RedirectResponse
    {
        $this->assertProductQuotable($product);

        $business = $product->business;
        $quote = $this->persist($business, $product, $this->calculate($request, $business, $product));

        return redirect()->route('quote.pdf', $quote);
    }

    public function result(Quote $quote): View
    {
        abort_unless($quote->isInternal(), 404);
        abort_unless($quote->business->is_template, 404);

        return view('superadmin.quotes.result', [
            'quote' => $quote,
            'business' => $quote->business,
            'product' => $quote->product,
        ]);
    }

    public function emailExisting(Quote $quote): RedirectResponse
    {
        abort_unless($quote->isInternal(), 404);
        abort_unless($quote->business->is_template, 404);

        $this->sendCustomerEmail($quote);

        return redirect()->route('superadmin.quotes.result', $quote)->with('status', 'Emailed to the customer.');
    }

    /**
     * Price-only calculation for review() — the wizard hands over just
     * the answers, nothing else, so that's all this validates.
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
     * notes, override, demo business identity, and the answers carried
     * through from review() as a hidden field) and runs the RulesEngine +
     * tax calculation again — no database write yet. Called fresh by
     * each of save()/email()/pdf().
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
            'demo_business_name' => ['nullable', 'string', 'max:255'],
            'demo_business_address' => ['nullable', 'string', 'max:255'],
            'demo_business_phone' => ['nullable', 'string', 'max:50'],
            'demo_business_email' => ['nullable', 'email', 'max:255'],
        ]);

        $snapshot = $product->published_snapshot;
        $rawAnswers = json_decode($validated['answers'] ?? '', true) ?: [];

        $processor = new QuoteAnswerProcessor;
        $answers = $processor->sanitizeAnswers($snapshot, $rawAnswers);
        $result = $processor->calculate($snapshot, $answers);

        $discount = (new DiscountCalculator)->apply($result['final_price'], $validated['discount_type'] ?? null, $validated['discount_value'] ?? null);

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

    private function persist(Business $business, Product $product, array $bundle): Quote
    {
        $validated = $bundle['validated'];

        $demoBusiness = array_filter([
            'name' => $validated['demo_business_name'] ?? null,
            'address' => $validated['demo_business_address'] ?? null,
            'phone' => $validated['demo_business_phone'] ?? null,
            'email' => $validated['demo_business_email'] ?? null,
        ]);

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
            'created_by' => null,
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
                'demo_business' => $demoBusiness ?: null,
                'selections' => (new QuoteAnswerProcessor)->buildSelections($product->published_snapshot, $bundle['answers']),
                'customer_contact' => (new CustomerResolver)->snapshotContact($customer),
            ],
        ]);

        (new QuoteAnswerProcessor)->recordAnswers($product, $quote, $product->published_snapshot, $bundle['answers']);

        return $quote;
    }

    private function sendCustomerEmail(Quote $quote): void
    {
        $result = ['applied_rules' => $quote->meta['applied_rules'] ?? []];

        $viewUrl = $quote->business->hasFeature('quote_customer_link')
            ? route('quote.view', $quote->uuid)
            : null;

        Mail::to($quote->customer_email)->send(new QuoteSubmittedToCustomer($quote, $result, $viewUrl));

        $quote->update(['emailed_at' => now()]);
    }

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
        abort_unless($product->business->is_template, 404);
        abort_unless($product->is_active, 404);
        abort_unless($product->published_snapshot, 404);
    }
}
