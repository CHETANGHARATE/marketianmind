<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Certificate $certificate
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Certificate Ready: {$this->certificate->course_title} — Marketian Mind",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.certificate_issued',
        );
    }
}