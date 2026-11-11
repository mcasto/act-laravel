<?php

namespace App\Helpers;

use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Mail;

/**
 * Mail::to() for every email sent to a patron (confirmations, reminders,
 * comp/Flex/Angel/class emails). CCs:
 *  - the admin address (config mail.admin_to, MAIL_TO_ADDRESS), so the
 *    box office has a copy of what each patron was sent — except where
 *    $ccAdmin is false (class messages to enrollees);
 *  - config('mail.cc_patrons') (CC_EMAIL) too, when CC is on.
 * Not used for staff notifications (those go to mail.admin_to directly) or
 * the sold-out notification list.
 */
class PatronMail
{
    public static function to(string $email, bool $ccAdmin = true): PendingMail
    {
        return Mail::to($email)->cc(self::ccFor($email, $ccAdmin));
    }

    /**
     * The CC addresses for an email to $email. Never the patron's own
     * address (e.g. staff buying their own tickets) — MailerSend rejects
     * the whole send if an address is both To and CC — and no duplicates.
     *
     * @return array<int, string>
     */
    public static function ccFor(?string $email, bool $ccAdmin = true): array
    {
        $candidates = [];

        if ($ccAdmin) {
            $candidates[] = config('mail.admin_to.address');
        }
        if (config('mail.cc_patrons.enabled')) {
            $candidates[] = config('mail.cc_patrons.address');
        }

        $to = strtolower(trim((string) $email));

        return collect($candidates)
            ->map(fn ($a) => trim((string) $a))
            ->filter()
            ->unique(fn ($a) => strtolower($a))
            ->reject(fn ($a) => strtolower($a) === $to)
            ->values()
            ->all();
    }
}
