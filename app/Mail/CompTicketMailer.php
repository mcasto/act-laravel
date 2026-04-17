<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompTicketMailer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.comp_from.address'),
                config('mail.comp_from.name')
            ),
            subject: 'Your Comp Ticket',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->data['view'],
            with: $this->data,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
