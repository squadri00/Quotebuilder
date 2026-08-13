<?php

namespace App\Mail;

use App\Models\SupportTicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the business, when an admin replies to their ticket.
 */
class SupportTicketReplied extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicketReply $reply)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Re: {$this->reply->ticket->subject} — {$this->reply->ticket->tracking_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-ticket-replied',
            with: [
                'reply' => $this->reply,
                'ticket' => $this->reply->ticket,
                'ticketUrl' => route('support.show', $this->reply->ticket),
            ],
        );
    }
}
