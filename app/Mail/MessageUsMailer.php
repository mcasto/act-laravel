<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageUsMailer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.contact_from.address'),
                config('mail.contact_from.name')
            ),
            subject: 'Message Us - Other Questions',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'message-us-mailer-template',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
