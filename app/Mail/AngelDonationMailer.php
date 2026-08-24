<?php

namespace App\Mail;

use App\Models\Angel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AngelDonationMailer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Angel $angel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.contact_from.address'),
                config('mail.contact_from.name')
            ),
            subject: 'New Angel Donation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'angel-donation-mailer-template',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
