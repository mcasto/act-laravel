<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'mail:test {email}';

    protected $description = 'Send a test email to the given address';

    public function handle(): void
    {
        $email = $this->argument('email');

        Mail::raw("This is a test email sent at " . now(), function ($message) use ($email) {
            $message
                ->from(config('mail.ticket_sale_from.address'), config('mail.ticket_sale_from.name'))
                ->to($email)
                ->subject('Test Email');
        });

        $this->info("Test email sent to {$email}");
    }
}
