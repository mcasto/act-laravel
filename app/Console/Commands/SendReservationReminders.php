<?php

namespace App\Console\Commands;

use App\Mail\ReservationReminderMailer;
use App\Models\CompTicket;
use App\Models\Performance;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReservationReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send reservation reminder emails for performances happening tomorrow';

    public function handle(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $performances = Performance::with('show')
            ->whereDate('date', $tomorrow)
            ->get();

        if ($performances->isEmpty()) {
            $this->info('No performances tomorrow.');
            return;
        }

        $performanceIds = $performances->pluck('id');
        $sent = 0;
        $failed = 0;

        // Map performance id -> formatted time for quick lookup
        $timeByPerformance = $performances->keyBy('id')->map(
            fn ($p) => Carbon::parse($p->start_time)->format('g:i A')
        );

        $showByPerformance = $performances->keyBy('id')->map(
            fn ($p) => $p->show?->name
        );

        // Ticket sales
        $ticketSales = TicketSale::with('patron')
            ->whereIn('performance_id', $performanceIds)
            ->get();

        foreach ($ticketSales as $sale) {
            $patron = $sale->patron;
            if (! $patron) continue;

            try {
                Mail::to($patron->email)->send(new ReservationReminderMailer([
                    'name'             => $patron->first_name . ' ' . $patron->last_name,
                    'show_name'        => $showByPerformance[$sale->performance_id],
                    'performance_time' => $timeByPerformance[$sale->performance_id],
                ]));
                $sent++;
            } catch (Exception $e) {
                $failed++;
                logger()->error('Failed to send reservation reminder', [
                    'patron_id' => $patron->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // Comp tickets (only redeemed ones with a specific performance booked)
        $compTickets = CompTicket::whereIn('performance_id', $performanceIds)
            ->whereNotNull('redeemed_at')
            ->get();

        foreach ($compTickets as $comp) {
            try {
                Mail::to($comp->email)->send(new ReservationReminderMailer([
                    'name'             => $comp->name,
                    'show_name'        => $showByPerformance[$comp->performance_id],
                    'performance_time' => $timeByPerformance[$comp->performance_id],
                ]));
                $sent++;
            } catch (Exception $e) {
                $failed++;
                logger()->error('Failed to send comp ticket reminder', [
                    'comp_ticket_id' => $comp->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reminders sent: {$sent}, failed: {$failed}");
    }
}
