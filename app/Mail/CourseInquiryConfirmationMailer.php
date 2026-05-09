<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseInquiryConfirmationMailer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.class_from.address'),
                config('mail.class_from.name')
            ),
            subject: 'Your Inquiry — ' . $this->data['course_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'course-inquiry-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
