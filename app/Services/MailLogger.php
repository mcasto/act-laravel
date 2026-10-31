<?php

namespace App\Services;

use App\Models\MailLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

/**
 * Keeps our own record of every outgoing email, since MailerSend's activity
 * history only reaches back a day or so — without this there was no way to
 * tell afterward whether a box-office notification was never sent or was
 * sent and lost somewhere downstream.
 *
 * Registered in AppServiceProvider::boot(). Logging must never be what
 * breaks an email, so every write here swallows its own errors.
 */
class MailLogger
{
    /** Ties the MessageSent event back to the row created on MessageSending. */
    private const HEADER = 'X-Mail-Log-Id';

    public function handleSending(MessageSending $event): void
    {
        try {
            $message = $event->message;

            $log = MailLog::create([
                'mailable' => $event->data['__laravel_mailable'] ?? null,
                'to'       => $this->addresses($message->getTo()),
                'cc'       => $this->addresses($message->getCc()),
                'bcc'      => $this->addresses($message->getBcc()),
                'subject'  => mb_substr((string) $message->getSubject(), 0, 255),
            ]);

            // Never reaches the recipient — the MailerSend driver only
            // forwards specific fields/headers, not arbitrary ones.
            $message->getHeaders()->addTextHeader(self::HEADER, (string) $log->id);
        } catch (\Throwable $e) {
            logger()->error('Failed to write mail log', ['error' => $e->getMessage()]);
        }
    }

    public function handleSent(MessageSent $event): void
    {
        try {
            $headers = $event->message->getHeaders();
            $logId = $headers->get(self::HEADER)?->getBodyAsString();

            if (! $logId) {
                return;
            }

            MailLog::whereKey($logId)->update([
                // Added to the message by the MailerSend driver after a
                // successful send; absent for other mailers (e.g. "log" locally).
                'mailersend_message_id' => $headers->get('X-MailerSend-Message-Id')?->getBodyAsString(),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to update mail log', ['error' => $e->getMessage()]);
        }
    }

    /** @param Address[] $addresses */
    private function addresses(array $addresses): ?string
    {
        return $addresses
            ? implode(', ', array_map(fn (Address $address) => $address->getAddress(), $addresses))
            : null;
    }
}
