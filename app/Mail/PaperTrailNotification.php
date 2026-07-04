<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaperTrailNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public array $emailData,
        public ?string $fromAddress = null,
        public ?string $fromName = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->fromAddress ? new Address($this->fromAddress, $this->fromName ?? config('mail.from.name')) : null,
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.papertrail-notification',
            text: 'emails.papertrail-notification-text',
            with: $this->emailData,
        );
    }
}
