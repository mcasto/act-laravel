<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketSaleMailer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public array $ticketData,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.ticket_sale_from.address'),
                config('mail.ticket_sale_from.name')
            ),
            subject: 'Ticket Sale Notification - ' . $this->ticketData['show']
                . self::ticketNumbersSuffix($this->ticketData['ticket_numbers'] ?? []),
        );
    }

    /**
     * " - #118, #119" — ticket numbers in the subject keep Gmail from
     * threading every sale for the same show into one conversation.
     */
    public static function ticketNumbersSuffix(array $ticketNumbers): string
    {
        if (empty($ticketNumbers)) {
            return '';
        }

        return ' - ' . implode(', ', array_map(fn ($n) => "#{$n}", $ticketNumbers));
    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'ticket-sale-mailer-template',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
