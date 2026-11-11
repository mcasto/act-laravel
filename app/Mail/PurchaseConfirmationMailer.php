<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseConfirmationMailer extends TemplatedMailable
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
            subject: self::withReference(
                'Your Ticket Confirmation - ' . $this->data['show_name'],
                $this->data['reference_number'] ?? null
            ),
        );
    }

    /**
     * "{subject} (Reference: 118, 119)" — the box office wants the ticket
     * numbers in patron-facing subjects too, so a patron's emails don't
     * all thread together in Gmail.
     */
    public static function withReference(string $subject, ?string $referenceNumber): string
    {
        return empty($referenceNumber)
            ? $subject
            : "{$subject} (Reference: {$referenceNumber})";
    }

    /** The email itself, plus the payment method's details section when it has one. */
    public function templateKeys(): array
    {
        return array_values(array_filter([$this->data['view'], $this->data['section_view'] ?? null]));
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
