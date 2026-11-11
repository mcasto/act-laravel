<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMailer extends TemplatedMailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Contact $contact,
    ) {}

    /**
     * Get the message envelope.
     */
    /** Replies go to the person who submitted it (see TemplatedMailable::submitter()). */
    protected function submitter(): ?array
    {
        return [$this->contact->email, $this->contact->name];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.contact_from.address'),
                config('mail.contact_from.name')
            ),
            subject: 'Website Contact',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'contact-mailer-template',
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
