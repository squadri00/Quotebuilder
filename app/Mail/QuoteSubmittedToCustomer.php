<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSubmittedToCustomer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array  $result  The RulesEngine::calculateFromSnapshot() output — carries
     *                         the applied-rules breakdown, which isn't persisted on the
     *                         Quote row itself (only its final total is).
     * @param  string|null  $viewUrl  The shareable /quote/view/{uuid} link — passed in
     *                                already resolved so this mailable doesn't need to
     *                                know about the quote_customer_link feature gate
     *                                itself; null just omits the "view again later" line.
     */
    public function __construct(public Quote $quote, public array $result = [], public ?string $viewUrl = null)
    {
    }

    public function envelope(): Envelope
    {
        $business = $this->quote->business;

        return new Envelope(
            subject: $this->quote->revises_quote_id
                ? "Your revised quote from {$business->name}"
                : "Your quote from {$business->name}",
            // Sent From the platform's own verified address (keeps
            // SPF/DKIM intact — see PublicQuoteController's docblock),
            // but a customer who hits Reply lands in the business's own
            // inbox instead, if they've set one.
            replyTo: $business->notification_email
                ? [new \Illuminate\Mail\Mailables\Address($business->notification_email, $business->name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote-submitted-customer',
            with: [
                'quote' => $this->quote,
                'business' => $this->quote->business,
                'product' => $this->quote->product,
                'appliedRules' => $this->result['applied_rules'] ?? [],
                'subtotalBeforeTax' => $this->quote->meta['subtotal_before_tax'] ?? null,
                'taxLines' => $this->quote->meta['tax_lines'] ?? [],
                'viewUrl' => $this->viewUrl,
            ],
        );
    }
}
