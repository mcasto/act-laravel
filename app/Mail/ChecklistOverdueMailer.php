<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the box office: checklist to-dos that just went overdue
 * (checklist:notify-overdue). $items: [{show, label, performance, due_label}].
 */
class ChecklistOverdueMailer extends TemplatedMailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $items, public string $link) {}

    public function envelope(): Envelope
    {
        $count = count($this->items);
        $first = $this->items[0] ?? [];

        return new Envelope(
            from: new Address(
                config('mail.ticket_sale_from.address'),
                config('mail.ticket_sale_from.name')
            ),
            subject: $count === 1
                ? "Overdue: {$first['label']} — {$first['show']}"
                : "{$count} box office checklist items are overdue",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'checklist-overdue');
    }

    public function attachments(): array
    {
        return [];
    }
}
