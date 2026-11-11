<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageUsMailer extends TemplatedMailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $body,
    ) {}

    /** Replies go to the person who submitted it (see TemplatedMailable::submitter()). */
    protected function submitter(): ?array
    {
        return [$this->email, null];
    }

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
