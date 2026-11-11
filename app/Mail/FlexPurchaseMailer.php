<?php

namespace App\Mail;

use App\Models\PatronFlexPackage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FlexPurchaseMailer extends TemplatedMailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PatronFlexPackage $package,
    ) {}

    /** Replies go to the person who submitted it (see TemplatedMailable::submitter()). */
    protected function submitter(): ?array
    {
        return [$this->package->patron?->email, trim(($this->package->patron?->first_name ?? '') . ' ' . ($this->package->patron?->last_name ?? ''))];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.contact_from.address'),
                config('mail.contact_from.name')
            ),
            subject: 'New Flex Purchase',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'flex-purchase-mailer-template',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
