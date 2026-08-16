<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PendingRegistration $pending, public string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name').' verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.registration-otp',
            with: [
                'code' => $this->code,
                'name' => $this->pending->name,
            ],
        );
    }
}
