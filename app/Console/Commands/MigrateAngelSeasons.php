<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrateAngelSeasons extends Command
{
    protected $signature = 'angels:migrate-seasons';
    protected $description = 'One-time: converts year_start/year_end columns on angels table to season string (e.g. 25-26)';

    public function handle(): void
    {
        // 1. Pull existing data before migration removes the columns
        $angels = DB::table('angels')->select('id', 'year_start', 'year_end')->get();

        $this->info("Pulled {$angels->count()} angel records.");

        // 2. Run pending migrations (adds `season`, removes `year_start`/`year_end`)
        Artisan::call('migrate', ['--force' => true]);
        $this->info(Artisan::output());

        // 3. Write season data back based on original year_start/year_end values
        foreach ($angels as $angel) {
            if ($angel->year_start && $angel->year_end) {
                $season = substr((string) $angel->year_start, -2) . '-' . substr((string) $angel->year_end, -2);
            } else {
                $season = null;
            }

            DB::table('angels')->where('id', $angel->id)->update(['season' => $season]);
        }

        $this->info("Season data written for {$angels->count()} angels.");
    }
}
