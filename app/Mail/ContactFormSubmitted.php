<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the platform's own contact address, from the public marketing
 * Contact page — a prospect/visitor inquiry, not tied to any Business.
 */
class ContactFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $topic,
        public string $message,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->topic}] New contact form message from {$this->name}",
            replyTo: [$this->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-form-submitted',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'topic' => $this->topic,
                'body' => $this->message,
            ],
        );
    }
}
