<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Freeform admin email to a class enrollee ("bring a notebook and pencil",
 * room change, etc.) — see CourseController::sendEnrolleeEmail(). A bulk
 * send is one of these per enrollee rather than a single BCC blast, same
 * as PaymentReminderMailer. Replies go to the instructor, since these are
 * about the class itself.
 */
class CourseMessageMailer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * $data: subject, body (sanitized HTML from the admin's editor), name,
     * course_name, instructor_name, instructor_email.
     */
    public function __construct(
        public array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.class_from.address'),
                config('mail.class_from.name')
            ),
            replyTo: [
                new Address(
                    $this->data['instructor_email'],
                    $this->data['instructor_name']
                ),
            ],
            subject: $this->data['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'course-message',
            with: $this->data,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
