<?php

namespace App\Helpers;

use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Mail;

/**
 * Mail::to() for the patron-facing confirmation emails (purchase, comp,
 * flex, angel donation, course enrollment) — CCs config('mail.cc_patrons')
 * when enabled, so someone can watch what patrons themselves are actually
 * receiving. Deliberately not used for admin/box-office-only notifications
 * (those already go to mail.admin_to, a separate concern) or the sold-out
 * notification list (a separate opt-in subscriber list, not patrons).
 */
class PatronMail
{
    public static function to(string $email): PendingMail
    {
        $mail = Mail::to($email);
        $cc = config('mail.cc_patrons.address');

        // Skipped when the patron IS the CC address (e.g. staff buying
        // their own tickets, or testing with your own email) — MailerSend
        // rejects the whole send if an address is both To and CC.
        if (config('mail.cc_patrons.enabled') && $cc && strcasecmp(trim($cc), trim($email)) !== 0) {
            $mail->cc($cc);
        }

        return $mail;
    }
}
