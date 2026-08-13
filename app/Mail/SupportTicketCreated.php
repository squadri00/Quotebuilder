<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the platform's admins, when a business opens a new ticket.
 */
class SupportTicketCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->ticket->tracking_number}] New support ticket — {$this->ticket->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-ticket-created',
            with: [
                'ticket' => $this->ticket,
                'business' => $this->ticket->business,
                'submitter' => $this->ticket->user,
                'ticketUrl' => route('superadmin.support.show', $this->ticket),
            ],
        );
    }
}
