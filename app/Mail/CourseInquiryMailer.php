<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseInquiryMailer extends Mailable
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
            replyTo: [
                new Address($this->data['email'], $this->data['first_name'] . ' ' . $this->data['last_name']),
            ],
            subject: 'Course Inquiry: ' . $this->data['course_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'course-inquiry-mailer',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
