<?php

namespace App\Console\Commands;

use App\Models\Performance;
use App\Models\TicketSale;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReservationReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Log the reservation reminder emails that would be sent for performances happening tomorrow';

    /**
     * Emails were previously actually sent here, but that path is disabled
     * for now after an erroneous send caused problems — instead of
     * Mail::send(), every email this command *would* send is appended as a
     * row to this CSV so the underlying selection logic (who gets emailed,
     * for which performance, how many tickets) can be verified against
     * real data over time before sending is turned back on. To resume
     * actually sending, swap logWouldSend() calls below for Mail::send()
     * calls (see git history prior to this change for the exact shape) —
     * and send via App\Helpers\PatronMail::to() instead of the raw Mail
     * facade, so these reminders also respect the CC/CC_EMAIL patron-cc
     * setting like every other patron-facing email does.
     */
    private const LOG_PATH = 'app/private/reservation-reminder-log.csv';

    public function handle(): void
    {
        $tomorrow = Carbon::tomorrow('America/Guayaquil')->toDateString();

        $performances = Performance::whereDate('date', $tomorrow)->get();

        if ($performances->isEmpty()) {
            $this->info('No performances tomorrow.');
            return;
        }

        $performanceIds = $performances->pluck('id');
        $logged = 0;

        // Map performance id -> date for quick lookup
        $dateByPerformance = $performances->keyBy('id')->map(
            fn($p) => Carbon::parse($p->date)->toDateString()
        );

        $sentEmails = [];

        // Comp tickets get their own mirrored TicketSale row on redemption
        // (see CompTixController::redeemComp()), so this one query already
        // covers both — no separate CompTicket pass needed anymore.
        $ticketSales = TicketSale::with('patron')
            ->whereIn('performance_id', $performanceIds)
            ->get();

        foreach ($ticketSales as $sale) {
            $patron = $sale->patron;
            if (! $patron || in_array($patron->email, $sentEmails)) continue;

            $this->logWouldSend($patron->email, $dateByPerformance[$sale->performance_id], $sale->quantity);
            $sentEmails[] = $patron->email;
            $logged++;
        }

        $this->info("Reminders logged (not sent): {$logged}");
    }

    /**
     * Appends one row to storage/app/private/reservation-reminder-log.csv —
     * creating it with a header row on first use. $quantity is the number
     * of tickets on the sale/comp reservation this reminder is for.
     */
    private function logWouldSend(string $recipient, string $performanceDate, int $quantity): void
    {
        $path = storage_path(self::LOG_PATH);
        $isNewFile = ! file_exists($path);

        $handle = fopen($path, 'a');

        if ($isNewFile) {
            fputcsv($handle, ['date_would_have_sent', 'recipient', 'performance_date', 'quantity'], ',', '"', '\\');
        }

        fputcsv($handle, [
            now('America/Guayaquil')->toDateString(),
            $recipient,
            $performanceDate,
            $quantity,
        ], ',', '"', '\\');

        fclose($handle);
    }
}
