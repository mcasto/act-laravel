<?php

namespace App\Console\Commands;

use App\Helpers\TheaterSeason;
use App\Models\Patron;
use App\Models\Performance;
use App\Models\TicketSale;
use Illuminate\Console\Command;

class SeedFlexTicketHistory2526 extends Command
{
    protected $signature = 'flex:seed-ticket-history-2526';
    protected $description = 'One-off: seed historical flex ticket usage for 2025-2026 season';

    const SEASON   = '25-26';
    const PM_FLEX  = 5;

    // Performance IDs for first performance of each show
    const PERF_J   = 78;   // Jukebox for the Algonquin
    const PERF_S   = 100;  // Sherlock Holmes and the Christmas Goose
    const PERF_M   = 73;   // Steel Magnolias
    const PERF_O   = 88;   // The Outsider
    const PERF_OUT = 76;   // The Outgoing Tide

    // [email, performance_id, quantity] — only non-zero rows included
    const HISTORY = [
        // Andersen (Seraphim Producer)
        ['sunni2018daze@gmail.com', self::PERF_J,   2],
        ['sunni2018daze@gmail.com', self::PERF_S,   2],
        ['sunni2018daze@gmail.com', self::PERF_M,   2],
        ['sunni2018daze@gmail.com', self::PERF_O,   2],

        // Blumenfeld (FLEX)
        ['runaroundsuervft@gmail.com', self::PERF_J,   2],
        ['runaroundsuervft@gmail.com', self::PERF_S,   2],
        ['runaroundsuervft@gmail.com', self::PERF_M,   2],
        ['runaroundsuervft@gmail.com', self::PERF_O,   2],
        ['runaroundsuervft@gmail.com', self::PERF_OUT, 2],

        // Carothers (Archangel)
        ['samcarothers7@gmail.com', self::PERF_J,   2],
        ['samcarothers7@gmail.com', self::PERF_S,   2],
        ['samcarothers7@gmail.com', self::PERF_M,   2],
        ['samcarothers7@gmail.com', self::PERF_O,   2],

        // Connelly (FLEX)
        ['suzanneeconnelly@gmail.com', self::PERF_J,   2],
        ['suzanneeconnelly@gmail.com', self::PERF_S,   2],
        ['suzanneeconnelly@gmail.com', self::PERF_M,   2],
        ['suzanneeconnelly@gmail.com', self::PERF_OUT, 4],

        // Culp (Guardian Angel)
        ['dinca.50@gmail.com', self::PERF_J,   1],
        ['dinca.50@gmail.com', self::PERF_S,   1],
        ['dinca.50@gmail.com', self::PERF_M,   1],
        ['dinca.50@gmail.com', self::PERF_O,   1],
        ['dinca.50@gmail.com', self::PERF_OUT, 1],

        // Deckard (Guardian Angel)
        ['singindabluze@gmail.com', self::PERF_J,   2],
        ['singindabluze@gmail.com', self::PERF_M,   2],
        ['singindabluze@gmail.com', self::PERF_O,   2],

        // Fiore (FLEX)
        ['kmf1925@aol.com', self::PERF_J,   2],
        ['kmf1925@aol.com', self::PERF_S,   2],
        ['kmf1925@aol.com', self::PERF_M,   2],
        ['kmf1925@aol.com', self::PERF_O,   4],

        // Fry (Guardian Angel)
        ['otomandbob@gmail.com', self::PERF_J,   2],
        ['otomandbob@gmail.com', self::PERF_S,   1],
        ['otomandbob@gmail.com', self::PERF_O,   1],
        ['otomandbob@gmail.com', self::PERF_OUT, 1],

        // Hemer (2-ticket credit, used at Sherlock)
        ['gjhemer@gmail.com', self::PERF_S, 2],

        // Holland (FLEX)
        ['lannwhite2002@yahoo.com', self::PERF_S,   2],
        ['lannwhite2002@yahoo.com', self::PERF_M,   2],
        ['lannwhite2002@yahoo.com', self::PERF_OUT, 4],

        // Howe (Seraphim Producer)
        ['amscert@gmail.com', self::PERF_J,   1],
        ['amscert@gmail.com', self::PERF_S,   1],
        ['amscert@gmail.com', self::PERF_M,   1],
        ['amscert@gmail.com', self::PERF_O,   1],
        ['amscert@gmail.com', self::PERF_OUT, 1],

        // James (Guardian Angel)
        ['drryanjames@gmail.com', self::PERF_J,   2],
        ['drryanjames@gmail.com', self::PERF_S,   2],
        ['drryanjames@gmail.com', self::PERF_M,   2],
        ['drryanjames@gmail.com', self::PERF_O,   2],
        ['drryanjames@gmail.com', self::PERF_OUT, 2],

        // Jamieson (FLEX)
        ['hiway2guy@gmail.com', self::PERF_O,   1],
        ['hiway2guy@gmail.com', self::PERF_OUT, 1],

        // Kayce (FLEX)
        ['cameronkayce@gmail.com', self::PERF_S,   2],
        ['cameronkayce@gmail.com', self::PERF_M,   1],
        ['cameronkayce@gmail.com', self::PERF_OUT, 2],

        // Lawrence-Howard (Guardian Angel)
        ['jamiehoward@me.com', self::PERF_J,   1],
        ['jamiehoward@me.com', self::PERF_S,   1],
        ['jamiehoward@me.com', self::PERF_M,   1],
        ['jamiehoward@me.com', self::PERF_O,   1],
        ['jamiehoward@me.com', self::PERF_OUT, 1],

        // Levine (FLEX)
        ['shlevine@gmail.com', self::PERF_J,   2],
        ['shlevine@gmail.com', self::PERF_M,   1],
        ['shlevine@gmail.com', self::PERF_OUT, 2],

        // Mccafferty (FLEX)
        ['jmccaffertylany@gmail.com', self::PERF_J,   1],
        ['jmccaffertylany@gmail.com', self::PERF_S,   2],
        ['jmccaffertylany@gmail.com', self::PERF_M,   2],
        ['jmccaffertylany@gmail.com', self::PERF_O,   1],

        // Mucklow (combined from Seraphim + FLEX packages)
        ['kmuck58@gmail.com', self::PERF_J,   1],
        ['kmuck58@gmail.com', self::PERF_S,   1],
        ['kmuck58@gmail.com', self::PERF_M,   2],
        ['kmuck58@gmail.com', self::PERF_O,   4],
        ['kmuck58@gmail.com', self::PERF_OUT, 2],

        // Olson (FLEX)
        ['olsonjohn01@gmail.com', self::PERF_J,   2],
        ['olsonjohn01@gmail.com', self::PERF_M,   3],

        // Osbourne (Seraphim Producer)
        ['kthsr8225@gmail.com', self::PERF_J,   2],
        ['kthsr8225@gmail.com', self::PERF_S,   2],
        ['kthsr8225@gmail.com', self::PERF_M,   2],
        ['kthsr8225@gmail.com', self::PERF_O,   2],
        ['kthsr8225@gmail.com', self::PERF_OUT, 2],

        // Patton (FLEX)
        ['karenbythesea@gmail.com', self::PERF_J,   2],
        ['karenbythesea@gmail.com', self::PERF_S,   2],
        ['karenbythesea@gmail.com', self::PERF_M,   2],

        // Phillips (FLEX)
        ['michaelrphillips53@gmail.com', self::PERF_J,   1],
        ['michaelrphillips53@gmail.com', self::PERF_S,   1],
        ['michaelrphillips53@gmail.com', self::PERF_M,   1],
        ['michaelrphillips53@gmail.com', self::PERF_O,   1],
        ['michaelrphillips53@gmail.com', self::PERF_OUT, 1],

        // Pruitt (FLEX)
        ['ecpruitt63@gmail.com', self::PERF_J,   2],
        ['ecpruitt63@gmail.com', self::PERF_S,   2],
        ['ecpruitt63@gmail.com', self::PERF_M,   2],
        ['ecpruitt63@gmail.com', self::PERF_O,   2],
        ['ecpruitt63@gmail.com', self::PERF_OUT, 2],

        // Sample (FLEX)
        ['elpmas2@yahoo.com', self::PERF_J,   2],
        ['elpmas2@yahoo.com', self::PERF_S,   2],
        ['elpmas2@yahoo.com', self::PERF_M,   2],
        ['elpmas2@yahoo.com', self::PERF_O,   2],
        ['elpmas2@yahoo.com', self::PERF_OUT, 2],

        // Schlattmann (FLEX)
        ['rschlattmann777@gmail.com', self::PERF_J,   1],
        ['rschlattmann777@gmail.com', self::PERF_S,   1],
        ['rschlattmann777@gmail.com', self::PERF_M,   1],
        ['rschlattmann777@gmail.com', self::PERF_O,   1],
        ['rschlattmann777@gmail.com', self::PERF_OUT, 1],

        // Shear (FLEX)
        ['shear89692@gmail.com', self::PERF_S, 3],
        ['shear89692@gmail.com', self::PERF_O, 2],

        // Shields (FLEX)
        ['ashields@gmail.com', self::PERF_J,   2],
        ['ashields@gmail.com', self::PERF_S,   1],
        ['ashields@gmail.com', self::PERF_M,   1],
        ['ashields@gmail.com', self::PERF_O,   2],

        // Shrader (FLEX)
        ['shraderh@gmail.com', self::PERF_S, 2],
        ['shraderh@gmail.com', self::PERF_M, 3],

        // Stovall (FLEX)
        ['stovallsh@gmail.com', self::PERF_J,   1],
        ['stovallsh@gmail.com', self::PERF_S,   2],
        ['stovallsh@gmail.com', self::PERF_M,   1],
        ['stovallsh@gmail.com', self::PERF_O,   1],
        ['stovallsh@gmail.com', self::PERF_OUT, 1],

        // Valle (FLEX)
        ['cafra2024@gmail.com', self::PERF_S, 3],
    ];

    public function handle(): int
    {
        $dates = TheaterSeason::datesForSeason(self::SEASON);

        $alreadySeeded = TicketSale::where('payment_method_id', self::PM_FLEX)
            ->whereHas('performance', fn ($q) => $q->whereBetween('date', [$dates['start'], $dates['end']]))
            ->exists();

        if ($alreadySeeded) {
            $this->error('Flex ticket history for ' . self::SEASON . ' already exists. Aborting.');
            return 1;
        }

        // Cache patron IDs and performance dates up front
        $patronIds = Patron::whereIn('email', array_unique(array_column(self::HISTORY, 0)))
            ->pluck('id', 'email');

        $perfDates = Performance::whereIn('id', [self::PERF_J, self::PERF_S, self::PERF_M, self::PERF_O, self::PERF_OUT])
            ->pluck('date', 'id');

        $created = 0;
        $skipped = 0;

        foreach (self::HISTORY as [$email, $perfId, $qty]) {
            if (!isset($patronIds[$email])) {
                $this->warn("  [SKIP] No patron for {$email}");
                $skipped++;
                continue;
            }

            TicketSale::create([
                'patron_id'         => $patronIds[$email],
                'payment_method_id' => self::PM_FLEX,
                'performance_id'    => $perfId,
                'quantity'          => $qty,
                'sold_at'           => $perfDates[$perfId] ?? now(),
                'no_show'           => 0,
            ]);

            $created++;
        }

        $this->info("Done. Records created: {$created}" . ($skipped ? ", Skipped: {$skipped}" : ''));
        return 0;
    }
}
