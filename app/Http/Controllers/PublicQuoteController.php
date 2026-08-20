<?php

namespace App\Http\Controllers;

use App\Mail\QuoteSubmittedToBusiness;
use App\Mail\QuoteSubmittedToCustomer;
use App\Models\Business;
use App\Models\Product;
use App\Models\Quote;
use App\Services\CustomerResolver;
use App\Services\QuoteAnswerProcessor;
use App\Services\TaxCalculator;
use App\Support\TurnstileVerifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * The public, no-login quote builder a business's customers use.
 *
 * IMPORTANT: these routes have no authenticated user, so the
 * BelongsToBusiness global scope (which only filters when someone is
 * logged in — see App\Models\Concerns\BelongsToBusiness) does NOT
 * restrict queries here. Every method below must explicitly confirm the
 * resolved product/quote actually belongs to the resolved business before
 * using it — that check is the only thing standing between two
 * businesses' data here.
 *
 * Everything shown and calculated here comes from the product's
 * published_snapshot — never the live/draft rows a business might
 * currently be editing. See App\Models\Product::buildPublishableSnapshot().
 *
 * Every feature below (email, PDF, shareable link) is gated by
 * Business::hasFeature() and checked here, server-side — never only by
 * hiding a button in the view. A business without a feature gets a 404
 * even if it guesses the URL. Templates are the one exception: they have
 * no Plan, so hasFeatureForQuoting() bypasses every gate (and the
 * monthly quote limit) for them, so Super Admin can preview the full
 * experience. See hasFeatureForQuoting().
 */
class PublicQuoteController extends Controller
{
    /**
     * The "Quote Hub" — a business-wide landing page listing whichever
     * products the business has chosen to feature here (see
     * QuoteHubController), in the order they picked. Lets the customer
     * pick which one they want a quote for, then continues into that
     * product's normal quote wizard. This is what a general-purpose QR
     * code (a storefront sticker, a business card) should link to, and
     * what the no-data-product variant of embed.js embeds; a
     * product-specific QR code or embed can skip this and link straight
     * to show() for that one product instead.
     */
    public function picker(Business $business): View
    {
        abort_unless($business->is_active, 404);

        $products = $business->products()
            ->where('is_active', true)
            ->where('show_in_quote_hub', true)
            ->whereNotNull('published_snapshot')
            // A product should always have a slug (HasSlug generates one on
            // creation), but route('quote.show', ...) throws for the whole
            // page if any single row here doesn't — excluding a stray one
            // is safer than a 500 hiding every other product in the hub.
            ->whereNotNull('slug')
            ->orderBy('quote_hub_sort_order')
            ->orderBy('name')
            ->get();

        return view('public.product-picker', [
            'business' => $business,
            'products' => $products,
        ]);
    }

    public function show(Business $business, Product $product): View
    {
        $this->assertProductBelongsToBusiness($business, $product);

        return view('public.quote-builder', [
            'business' => $business,
            'product' => $product,
            'snapshot' => $product->published_snapshot,
        ]);
    }

    public function store(Request $request, Business $business, Product $product): View|RedirectResponse
    {
        $this->assertProductBelongsToBusiness($business, $product);

        // Blocked IPs never even reach the honeypot/Turnstile checks — a
        // business owner who's already identified and blocked a specific
        // nuisance IP (Customers page) shouldn't need it to keep passing
        // those checks every time first. Scoped to this business only,
        // same as the Customer record itself.
        if ($business->customers()->where('ip_address', $request->ip())->where('is_blocked', true)->exists()) {
            return view('public.quote-unavailable', [
                'business' => $business,
                'product' => $product,
            ]);
        }

        // Honeypot: a field real customers never see or fill in (hidden
        // off-screen in quote-builder.blade.php), but a bot filling the
        // form out blindly usually does. Bail out quietly — no error, no
        // hint anything was detected — rather than creating a Quote and
        // Customer row from it.
        if ($request->filled('website')) {
            return redirect()->route('quote.show', [$business, $product]);
        }

        if (! app(TurnstileVerifier::class)->passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withErrors(['turnstile' => "We couldn't verify you're not a robot — please try again."]);
        }

        if (! $business->is_template && $business->hasReachedMonthlyQuoteLimit()) {
            return view('public.quote-unavailable', [
                'business' => $business,
                'product' => $product,
            ]);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'answers' => ['nullable', 'string'],
        ]);

        $snapshot = $product->published_snapshot;
        $rawAnswers = json_decode($validated['answers'] ?? '', true) ?: [];

        $processor = new QuoteAnswerProcessor;
        $answers = $processor->sanitizeAnswers($snapshot, $rawAnswers);
        $result = $processor->calculate($snapshot, $answers);

        // Tax is computed on top of the RulesEngine's final_price (the
        // pre-tax subtotal) and folded into what the customer is actually
        // quoted — final_price on the Quote itself is the tax-inclusive
        // total, matching what they'll actually owe.
        $tax = (new TaxCalculator)->calculate($business, $result['final_price']);

        $customer = (new CustomerResolver)->findOrCreate($business, [
            'name' => $validated['customer_name'],
            'email' => $validated['customer_email'],
            'ip_address' => $request->ip(),
        ]);

        $quote = Quote::create([
            'business_id' => $business->id,
            'reference_number' => $business->nextQuoteReferenceNumber(),
            'product_id' => $product->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_id' => $customer->id,
            'final_price' => $tax['total'],
            // Business-configurable, applies only to quotes customers
            // build themselves — see Business Settings' "Public Quote
            // Settings" section. Null (the default) means no expiration,
            // same as before this setting existed.
            'expires_at' => $business->public_quote_validity_days
                ? now()->addDays($business->public_quote_validity_days)
                : null,
            // Snapshotted so the PDF, the shareable link, and any later
            // re-render always show exactly what the customer was quoted —
            // independent of the product being edited/republished, or the
            // business's tax rates changing, after the fact.
            'meta' => [
                'applied_rules' => $result['applied_rules'],
                'base_price' => $snapshot['product']['base_price'] ?? 0,
                'subtotal_before_tax' => $tax['subtotal'],
                'tax_lines' => $tax['tax_lines'],
                'tax_total' => $tax['tax_total'],
                'selections' => $processor->buildSelections($snapshot, $answers),
                'customer_contact' => (new CustomerResolver)->snapshotContact($customer),
            ],
        ]);

        $processor->recordAnswers($product, $quote, $snapshot, $answers);

        $viewUrl = $this->hasFeatureForQuoting($business, 'quote_customer_link')
            ? route('quote.view', $quote->uuid)
            : null;

        if ($this->emailEnabledForQuoting($business)) {
            $this->sendNotificationEmails($business, $quote, $result, $viewUrl);
        }

        return view('public.quote-result', [
            'business' => $business,
            'product' => $product,
            'quote' => $quote,
            'result' => $result,
            'tax' => $tax,
            'selections' => $quote->meta['selections'] ?? [],
            'pdfEnabled' => $this->pdfEnabledForQuoting($business),
            'viewUrl' => $viewUrl,
            'startOverUrl' => $this->startOverUrl($business, $product),
        ]);
    }

    /**
     * The shareable "view your quote again later" link. Always resolvable
     * by ID/uuid alone (no business/product in the URL), so the feature
     * gate here is what actually protects it — a valid uuid for a
     * business without quote_customer_link still 404s.
     */
    public function viewByUuid(string $uuid): View
    {
        $quote = Quote::withoutGlobalScopes()->where('uuid', $uuid)->firstOrFail();
        $business = $quote->business;
        $product = $quote->product;

        abort_unless($this->hasFeatureForQuoting($business, 'quote_customer_link'), 404);

        return view('public.quote-result', [
            'business' => $business,
            'product' => $product,
            'quote' => $quote,
            'result' => ['applied_rules' => $quote->meta['applied_rules'] ?? []],
            'tax' => $this->taxFromMeta($quote),
            'selections' => $quote->meta['selections'] ?? [],
            'pdfEnabled' => $this->pdfEnabledForQuoting($business),
            'viewUrl' => route('quote.view', $quote->uuid),
            'revisiting' => true,
            'startOverUrl' => $product ? $this->startOverUrl($business, $product) : route('quote.picker', $business),
        ]);
    }

    /**
     * Where "Start a new quote" on the result page should go — the
     * product picker (so the customer can also pick a different product)
     * when this business actually has one to show, otherwise back into
     * this same product's wizard. Quote Hub visibility defaults off (see
     * the products migration), so a business that's never turned it on
     * would otherwise send every customer to an empty picker page.
     */
    private function startOverUrl(Business $business, Product $product): string
    {
        $hasHubProducts = $business->products()
            ->where('is_active', true)
            ->where('show_in_quote_hub', true)
            ->whereNotNull('published_snapshot')
            ->exists();

        return $hasHubProducts
            ? route('quote.picker', $business)
            : route('quote.show', [$business, $product]);
    }

    public function downloadPdf(Quote $quote): Response
    {
        $business = $quote->business;
        $product = $quote->product;

        // Staff-created quotes always get PDF, regardless of plan — see
        // InternalQuoteController's docblock. Customer-submitted ones
        // stay gated by the business's actual plan feature (and the
        // business's own on/off switch on top of that) except on
        // templates — see hasFeatureForQuoting()/pdfEnabledForQuoting().
        abort_unless($quote->isInternal() || $this->pdfEnabledForQuoting($business), 404);

        $pdf = Pdf::loadView('public.quote-pdf', [
            'business' => $business,
            'product' => $product,
            'quote' => $quote,
            'appliedRules' => $quote->meta['applied_rules'] ?? [],
            'basePrice' => $quote->meta['base_price'] ?? 0,
            'tax' => $this->taxFromMeta($quote),
        ]);

        $filename = 'quote-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', $quote->displayReference()).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Rebuilds the same shape TaxCalculator::calculate() returns, but from
     * a quote's own immutable meta snapshot rather than a live calculation
     * — so a quote always shows the tax that actually applied when it was
     * submitted, even if the business's tax rates change afterward.
     * Quotes submitted before this feature existed have no tax_lines in
     * meta at all, so this falls back to "no tax" for those.
     */
    private function taxFromMeta(Quote $quote): array
    {
        return [
            'subtotal' => (float) ($quote->meta['subtotal_before_tax'] ?? $quote->final_price),
            'tax_lines' => $quote->meta['tax_lines'] ?? [],
            'tax_total' => (float) ($quote->meta['tax_total'] ?? 0),
            'total' => (float) $quote->final_price,
        ];
    }

    /**
     * The quote itself is already saved by the time this runs (see
     * store() — Quote::create() happens well before this is called), so
     * a mail server problem here (bad SMTP credentials, the provider
     * rejecting this server's IP, a timeout, ...) must never take the
     * customer's already-successful submission down with it. Each send
     * is caught independently and logged rather than thrown, so one
     * failing recipient (or a total outage) still lets every other send
     * attempt run and always leaves the customer looking at their result
     * page instead of a 500 error.
     */
    private function sendNotificationEmails(Business $business, Quote $quote, array $result, ?string $viewUrl): void
    {
        try {
            Mail::to($quote->customer_email)->send(
                new QuoteSubmittedToCustomer($quote, $result, $viewUrl)
            );
        } catch (\Throwable $e) {
            report($e);
        }

        $inboxUrl = $business->hasFeature('quote_inbox') ? route('quotes.index') : null;

        $recipients = $business->users()->where('is_active', true)->pluck('email');

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new QuoteSubmittedToBusiness($quote, $inboxUrl));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function assertProductBelongsToBusiness(Business $business, Product $product): void
    {
        abort_unless($product->business_id === $business->id, 404);
        abort_unless($business->is_active, 404);
        abort_unless($product->is_active, 404);
        abort_unless($product->published_snapshot, 404);
    }

    /**
     * Templates have no Plan, so every Plan-gated feature would otherwise
     * read as "off" here — but Super Admin needs to see the real
     * customer-facing experience (final price, PDF, shareable link,
     * email) when testing a template through Preview. Templates bypass
     * every quote-flow feature gate; real businesses stay gated by their
     * actual plan, unchanged.
     */
    private function hasFeatureForQuoting(Business $business, string $key): bool
    {
        return $business->is_template || $business->hasFeature($key);
    }

    /**
     * The business's own on/off switch (Business Settings → Public Quote
     * Settings) layered on top of the plan feature — it can only ever
     * turn PDF download OFF for public quotes, never on: a business
     * without pdf_download on their plan gets false here regardless of
     * how this switch is set, since hasFeatureForQuoting() is checked
     * first with a real AND.
     */
    private function pdfEnabledForQuoting(Business $business): bool
    {
        return $this->hasFeatureForQuoting($business, 'pdf_download') && $business->public_pdf_download_enabled;
    }

    /**
     * Same "can only turn off, never on" relationship to the plan
     * feature as pdfEnabledForQuoting() above, just for email_notifications.
     */
    private function emailEnabledForQuoting(Business $business): bool
    {
        return $this->hasFeatureForQuoting($business, 'email_notifications') && $business->public_email_enabled;
    }
}
