<?php

namespace App\Console\Commands;

use App\Models\Show;
use App\Models\TicketSale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTicketIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-ticket-ids
        {--current-show=Driving Miss Daisy : Name of the show currently on sale}
        {--dry-run : Report what would happen without writing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-time backfill: issues internal ticket IDs for existing ticket sales that predate the feature, marking every show before --current-show as fully redeemed by default';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $currentShowName = $this->option('current-show');

        $currentShow = Show::where('name', $currentShowName)->first();

        if (! $currentShow) {
            $this->error("Show \"{$currentShowName}\" not found.");

            return self::FAILURE;
        }

        $currentShowStart = $currentShow->performances()->min('date');

        if (! $currentShowStart) {
            $this->error("\"{$currentShowName}\" has no performances — can't tell which shows are prior to it.");

            return self::FAILURE;
        }

        $this->info("Current show: {$currentShowName} (opens {$currentShowStart})");

        // A show counts as "prior" only if its entire run finished before the
        // current show opens — not just its first performance, so a run that
        // overlaps the current show's opening isn't mistakenly auto-redeemed.
        $priorShows = Show::has('performances')
            ->get()
            ->filter(fn (Show $show) => $show->id !== $currentShow->id
                && $show->performances()->max('date') < $currentShowStart);

        $priorShowIds = $priorShows->pluck('id');

        $this->info('Shows treated as prior (tickets auto-redeemed): ' . $priorShows->pluck('name')->implode(', '));
        $this->newLine();

        $sales = TicketSale::whereDoesntHave('tickets')
            ->with('patron', 'performance.show')
            ->join('performances', 'ticket_sales.performance_id', '=', 'performances.id')
            ->orderBy('ticket_sales.sold_at')
            ->select('ticket_sales.*')
            ->get();

        if ($sales->isEmpty()) {
            $this->info('No ticket sales need backfilling — every sale already has ticket IDs.');

            return self::SUCCESS;
        }

        $this->info("{$sales->count()} ticket sale(s) need ticket IDs.");

        if ($dryRun) {
            $bySh = $sales->groupBy(fn ($sale) => $sale->performance->show->name);
            foreach ($bySh as $showName => $showSales) {
                $isPrior = $priorShowIds->contains($showSales->first()->performance->show_id);
                $this->line(sprintf(
                    '  %s%s — %d sale(s), %d ticket(s)',
                    $showName,
                    $isPrior ? ' [auto-redeemed]' : '',
                    $showSales->count(),
                    $showSales->sum('quantity'),
                ));
            }
            $this->newLine();
            $this->info('Dry run — nothing was written.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($sales->count());
        $bar->start();

        DB::transaction(function () use ($sales, $priorShowIds, $bar) {
            foreach ($sales as $sale) {
                $purchaserName = trim(($sale->patron->first_name ?? '') . ' ' . ($sale->patron->last_name ?? ''));
                $sale->issueTickets($purchaserName !== '' ? $purchaserName : 'Guest');

                if ($priorShowIds->contains($sale->performance->show_id)) {
                    $sale->tickets()->update(['redeemed_at' => now()]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Backfilled ticket IDs for {$sales->count()} ticket sale(s).");

        return self::SUCCESS;
    }
}
