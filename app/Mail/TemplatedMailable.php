<?php

namespace App\Mail;

use App\Support\EmailTemplates;
use Illuminate\Mail\Mailable;

/**
 * Base for the site's emails: skips sending when the email has been
 * switched off on the admin Email Templates page (App\Support\EmailTemplates).
 * Covers queued sends too, since those also end in send().
 */
abstract class TemplatedMailable extends Mailable
{
    /**
     * The templates this email is built from — all must be switched on for
     * it to go out. Keys are view names.
     *
     * @return array<int, string>
     */
    public function templateKeys(): array
    {
        return [$this->content()->view];
    }

    public function send($mailer)
    {
        foreach ($this->templateKeys() as $key) {
            if (! EmailTemplates::isEnabled($key)) {
                return null;
            }
        }

        $this->applyDefaultReplyTo();

        return parent::send($mailer);
    }

    /**
     * For a staff notice about something a person submitted (a form, a
     * purchase, a donation): that person, as [email, name], so staff can
     * reply straight to them. Null for every other email.
     *
     * @return array{0: ?string, 1: ?string}|null
     */
    protected function submitter(): ?array
    {
        return null;
    }

    /**
     * Who replies go to, unless the email already names someone (e.g. a
     * class message replies to its instructor): the submitter for staff
     * notices, otherwise MAIL_REPLY_TO (config mail.default_reply_to).
     */
    protected function applyDefaultReplyTo(): void
    {
        if (! empty($this->replyTo) || ! empty($this->envelope()->replyTo)) {
            return;
        }

        [$email, $name] = $this->submitter() ?? [null, null];
        $email = trim((string) $email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->replyTo($email, trim((string) $name) ?: null);
            return;
        }

        if ($address = config('mail.default_reply_to.address')) {
            $this->replyTo($address, config('mail.default_reply_to.name'));
        }
    }
}
