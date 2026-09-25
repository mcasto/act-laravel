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

        if (config('mail.cc_patrons.enabled') && config('mail.cc_patrons.address')) {
            $mail->cc(config('mail.cc_patrons.address'));
        }

        return $mail;
    }
}
