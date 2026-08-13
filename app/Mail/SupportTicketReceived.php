<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the business, confirming their new ticket was received.
 */
class SupportTicketReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We've received your support request — {$this->ticket->tracking_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-ticket-received',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => route('support.show', $this->ticket),
            ],
        );
    }
}
