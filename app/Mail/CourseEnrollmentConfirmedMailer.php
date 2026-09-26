<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the course instructor only once an enrollment's payment is
 * actually confirmed — instantly for a free course or a Fixr (credit card)
 * enrollment, or later when an admin verifies a PayPal/Transfer payment
 * (see CourseController::updateConfirmed()). Deliberately not sent at
 * initial submission — that goes to the box office instead
 * (CourseInquiryMailer), since the instructor may not have admin access to
 * verify payments and shouldn't have to track who's still pending.
 */
class CourseEnrollmentConfirmedMailer extends Mailable
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
            subject: 'Enrollment Confirmed: ' . $this->data['course_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'course-enrollment-confirmed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
