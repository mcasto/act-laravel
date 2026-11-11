<?php

namespace App\Console\Commands;

use App\Helpers\LocalTime;
use App\Helpers\PatronMail;
use App\Mail\ReservationReminderMailer;
use App\Models\Performance;
use App\Models\ReservationReminder;
use App\Models\TicketSale;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Reservation reminder emails, in two batches per performance:
 *
 *  - day_before (`reminders:send`, scheduled daily at 10:00 local): every
 *    patron holding tickets for a performance happening tomorrow.
 *
 *  - same_day (`reminders:send --same-day`, scheduled every five minutes):
 *    once a performance is within 4 hours of its start time, every patron
 *    holding tickets for it who has NOT already been reminded for it —
 *    i.e. purchases (or reschedules onto it) made after the day_before
 *    batch ran. Runs once per performance (performances.same_day_reminders_at).
 *
 * Comp tickets get their own mirrored TicketSale row on redemption (see
 * CompTixController::redeemComp()), so querying TicketSale covers both.
 *
 * Sending is off unless config('mail.reservation_reminders.send') is true
 * (RESERVATION_REMINDERS_SEND). While off, every email that WOULD be sent
 * is recorded in reservation_reminders with status 'logged' and appended to
 * a CSV in storage/app/private, so the selection logic can be verified
 * against real data before sending is turned on.
 */
class SendReservationReminders extends Command
{
    protected $signature = 'reminders:send {--same-day : Send the same-day batch (4 hours before curtain) instead of the day-before batch}';

    protected $description = 'Send (or log) reservation reminder emails — the day before a performance, or 4 hours before curtain for anyone not yet reminded';

    /** How long before curtain the same-day batch goes out. */
    public const SAME_DAY_HOURS_BEFORE = 4;

    private const CSV_LOGS = [
        ReservationReminder::BATCH_DAY_BEFORE => 'app/private/reservation-reminder-log.csv',
        ReservationReminder::BATCH_SAME_DAY   => 'app/private/reservation-reminder-same-day-log.csv',
    ];

    public function handle(): void
    {
        $this->option('same-day') ? $this->sameDay() : $this->dayBefore();
    }

    private function dayBefore(): void
    {
        $tomorrow = LocalTime::now()->addDay()->toDateString();
        $performances = Performance::with('show')->whereDate('date', $tomorrow)->get();

        if ($performances->isEmpty()) {
            $this->info('No performances tomorrow.');
            return;
        }

        foreach ($performances as $performance) {
            $this->remind($performance, ReservationReminder::BATCH_DAY_BEFORE);
        }
    }

    private function sameDay(): void
    {
        $now = LocalTime::now();

        $performances = Performance::with('show')
            ->whereDate('date', $now->toDateString())
            ->whereNull('same_day_reminders_at')
            ->get()
            ->filter(function (Performance $p) use ($now) {
                $start = self::startsAt($p);
                return $now->gte($start->copy()->subHours(self::SAME_DAY_HOURS_BEFORE)) && $now->lt($start);
            });

        foreach ($performances as $performance) {
            // Claim the batch first so an overlapping run can't double-send.
            $claimed = Performance::whereKey($performance->id)
                ->whereNull('same_day_reminders_at')
                ->update(['same_day_reminders_at' => now()]);

            if ($claimed) {
                $this->remind($performance, ReservationReminder::BATCH_SAME_DAY);
            }
        }
    }

    private function remind(Performance $performance, string $batch): void
    {
        $alreadyReminded = ReservationReminder::where('performance_id', $performance->id)
            ->pluck('email')
            ->map(fn($e) => strtolower($e))
            ->all();

        $recipients = $this->recipients($performance)
            ->reject(fn($r) => in_array(strtolower($r['email']), $alreadyReminded));

        $send = config('mail.reservation_reminders.send');
        $counts = ['sent' => 0, 'logged' => 0, 'failed' => 0];

        foreach ($recipients as $r) {
            $status = 'logged';
            $error = null;

            if ($send) {
                try {
                    PatronMail::to($r['email'])->send(new ReservationReminderMailer([
                        'name'             => $r['name'],
                        'show_name'        => $performance->show?->name,
                        'performance_time' => $performance->formatted_time,
                        'day'              => $batch === ReservationReminder::BATCH_SAME_DAY ? 'today' : 'tomorrow',
                    ]));
                    $status = 'sent';
                } catch (Throwable $e) {
                    $status = 'failed';
                    $error = $e->getMessage();
                    report($e);
                }
            }

            ReservationReminder::create([
                'performance_id' => $performance->id,
                'patron_id'      => $r['patron_id'],
                'email'          => $r['email'],
                'batch'          => $batch,
                'quantity'       => $r['quantity'],
                'status'         => $status,
                'error'          => $error,
            ]);

            $this->appendCsv($batch, $r['email'], self::startsAt($performance), $r['quantity']);
            $counts[$status]++;
        }

        $this->info(sprintf(
            '%s %s (%s): sent %d, logged %d, failed %d',
            $batch,
            $performance->show?->name,
            self::startsAt($performance)->format('Y-m-d g:i A'),
            $counts['sent'],
            $counts['logged'],
            $counts['failed'],
        ));
    }

    /**
     * One entry per patron email still holding tickets for the
     * performance, with the seats they hold (quantity - cancelled) summed
     * across their sales for it. A fully cancelled sale is left out, so a
     * patron whose every ticket was cancelled gets no reminder.
     */
    private function recipients(Performance $performance): Collection
    {
        return TicketSale::with('patron')
            ->where('performance_id', $performance->id)
            ->notFullyCancelled()
            ->get()
            ->filter(fn($sale) => $sale->patron && $sale->patron->email)
            ->groupBy(fn($sale) => strtolower(trim($sale->patron->email)))
            ->map(fn($sales) => [
                'patron_id' => $sales->first()->patron->id,
                'email'     => trim($sales->first()->patron->email),
                'name'      => trim($sales->first()->patron->first_name . ' ' . $sales->first()->patron->last_name),
                'quantity'  => $sales->sum('active_quantity'),
            ])
            ->values();
    }

    private static function startsAt(Performance $performance): Carbon
    {
        return LocalTime::parse(
            Carbon::parse($performance->date)->toDateString() . ' ' . $performance->start_time
        );
    }

    /**
     * Appends one row to the batch's CSV log, creating it with a header row
     * on first use. The first column is the local time the reminder was
     * (or would have been) sent.
     */
    private function appendCsv(string $batch, string $recipient, Carbon $startsAt, int $quantity): void
    {
        $path = storage_path(self::CSV_LOGS[$batch]);
        $isNewFile = ! file_exists($path);

        $handle = fopen($path, 'a');

        if ($isNewFile) {
            fputcsv($handle, ['date_would_have_sent', 'recipient', 'performance_date', 'quantity'], ',', '"', '\\');
        }

        fputcsv($handle, [
            $batch === ReservationReminder::BATCH_SAME_DAY
                ? LocalTime::now()->format('Y-m-d H:i')
                : LocalTime::todayString(),
            $recipient,
            $startsAt->toDateString(),
            $quantity,
        ], ',', '"', '\\');

        fclose($handle);
    }
}
