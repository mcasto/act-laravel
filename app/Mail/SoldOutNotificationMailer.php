<?php

namespace App\Mail;

use App\Models\Performance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SoldOutNotificationMailer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Performance $performance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.contact_from.address'),
                config('mail.contact_from.name')
            ),
            subject: "SOLD OUT: {$this->performance->show->name} — {$this->performance->formatted_date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sold-out-notification-mailer-template',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
