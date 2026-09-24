<?php

namespace App\Console\Commands;

use App\Models\TicketSale;
use Illuminate\Console\Command;

class BackfillDoorNames extends Command
{
    protected $signature = 'ticket-sales:backfill-door-names';

    protected $description = "One-time: fills door_last/door_first on existing ticket sales from their patron's current name";

    public function handle(): void
    {
        $sales = TicketSale::withTrashed()
            ->where(function ($query) {
                $query->whereNull('door_last')->orWhereNull('door_first');
            })
            ->with('patron')
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($sales as $sale) {
            if (! $sale->patron) {
                $this->warn("Skipped ticket sale #{$sale->id} — no linked patron.");
                $skipped++;
                continue;
            }

            $sale->update([
                'door_last'  => $sale->door_last ?? $sale->patron->last_name,
                'door_first' => $sale->door_first ?? $sale->patron->first_name,
            ]);
            $updated++;
        }

        $this->info("Backfilled door name on {$updated} ticket sale(s)." . ($skipped ? " Skipped {$skipped} with no patron." : ''));
    }
}
