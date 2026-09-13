<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketingAutomationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $bodyHtml,
        public ?string $unsubscribeUrl = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing_automation',
            with: [
                'emailSubject' => $this->emailSubject,
                'bodyHtml' => $this->bodyHtml,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]
        );
    }
}
