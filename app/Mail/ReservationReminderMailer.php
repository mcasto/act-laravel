<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationReminderMailer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.ticket_sale_from.address'),
                config('mail.ticket_sale_from.name')
            ),
            subject: 'Reminder: Your Performance Tomorrow - ' . $this->data['show_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'reservation-reminder',
            with: $this->data,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
