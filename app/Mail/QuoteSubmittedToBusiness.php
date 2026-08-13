<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSubmittedToBusiness extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string|null  $inboxUrl  The business's Quote Inbox link — only passed in
     *                                 when they have the quote_inbox feature; null just
     *                                 omits the link rather than pointing somewhere
     *                                 they can't actually reach.
     */
    public function __construct(public Quote $quote, public ?string $inboxUrl = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New quote from {$this->quote->customer_name}",
            // So a staff member can just hit Reply to respond straight to
            // the customer who requested the quote.
            replyTo: [new \Illuminate\Mail\Mailables\Address($this->quote->customer_email, $this->quote->customer_name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote-submitted-business',
            with: [
                'quote' => $this->quote,
                'business' => $this->quote->business,
                'product' => $this->quote->product,
                'inboxUrl' => $this->inboxUrl,
            ],
        );
    }
}
