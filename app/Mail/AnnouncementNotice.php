<?php

namespace App\Mail;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Announcement $announcement, public string $recipientName)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[".config('app.name')."] {$this->announcement->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.announcement-notice',
            with: [
                'announcement' => $this->announcement,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}
