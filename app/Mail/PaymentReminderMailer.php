<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Box office's "your payment hasn't come through yet" nudge for unconfirmed
 * PayPal / Bank Transfer reservations — sent individually per patron (see
 * TicketSaleController::sendPaymentReminders()) rather than one BCC blast,
 * which trips spam filters once the list gets long.
 */
class PaymentReminderMailer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * $data: subject, body (plain text from the admin), name, and
     * reservations (each: show_name, performance_date, performance_time,
     * num_tickets, payment_method, reference_number).
     */
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
            replyTo: [
                new Address(
                    config('mail.admin_to.address'),
                    config('mail.admin_to.name')
                ),
            ],
            subject: PurchaseConfirmationMailer::withReference(
                $this->data['subject'],
                collect($this->data['reservations'] ?? [])
                    ->pluck('reference_number')
                    ->filter()
                    ->implode(', ')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'payment-reminder',
            with: $this->data,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
