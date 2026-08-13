<?php

namespace App\Mail;

use App\Models\SupportTicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the platform's admins, when a business replies to their own ticket.
 */
class SupportTicketBusinessReplied extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicketReply $reply)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->reply->ticket->tracking_number}] New reply from {$this->reply->ticket->business->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-ticket-business-replied',
            with: [
                'reply' => $this->reply,
                'ticket' => $this->reply->ticket,
                'ticketUrl' => route('superadmin.support.show', $this->reply->ticket),
            ],
        );
    }
}
