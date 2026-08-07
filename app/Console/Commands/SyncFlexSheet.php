<?php

namespace App\Console\Commands;

use App\Helpers\TheaterSeason;
use App\Models\Patron;
use App\Models\Performance;
use App\Models\Show;
use App\Models\TicketSale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFlexSheet extends Command
{
    protected $signature = 'sync:flex-sheet {--dry-run : Preview changes without writing to DB}';
    protected $description = 'Sync flex ticket usage from the Google Sheet into the database';

    const SHEET_URL = 'https://docs.google.com/spreadsheets/d/1tkY2fmKXk8ChIDHIg_gI0XudJPkWMVy_FfRLGrW1n3g/export?format=csv';
    const PM_FLEX   = 5;
    const SEASON    = '25-26';

    // Maps a keyword (found in the sheet column header) to a show name LIKE pattern for DB lookup.
    // Shows are additionally filtered to only those with performances in the current season,
    // which prevents matching same-named shows from prior seasons (e.g. "Moonlight and Magnolias").
    // 'ingale' matches both 'nightingale' and 'nighingale' (the sheet's misspelling).
    const SHOW_KEYWORDS = [
        'jukebox'  => '%jukebox%',
        'sherlock' => '%sherlock%',
        'magnolia' => '%magnolia%',
        'outsider' => '%outsider%',
        'outgoing' => '%outgoing%',
        'ingale'   => '%nightingal%',
    ];

    public function handle(): int
    {
        $start = now();
        // Log::info('sync:flex-sheet started', ['at' => $start->toDateTimeString()]);

        try {
            return $this->sync();
        } finally {
            $end = now();
            // Log::info('sync:flex-sheet finished', [
            //     'at' => $end->toDateTimeString(),
            //     'duration_seconds' => $end->timestamp - $start->timestamp,
            // ]);
        }
    }

    private function sync(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('[DRY RUN — no changes will be written]');
        }

        // 1. Fetch the CSV from Google Sheets (redirect is followed automatically)
        $this->info('Fetching Google Sheet...');
        try {
            $response = Http::timeout(30)->get(self::SHEET_URL);
        } catch (\Exception $e) {
            $this->error('Fetch failed: ' . $e->getMessage());
            Log::error('sync:flex-sheet fetch failed', ['error' => $e->getMessage()]);
            return 1;
        }

        if (!$response->ok()) {
            $this->error('Sheet returned HTTP ' . $response->status());
            return 1;
        }

        // 2. Hash-check — skip the rest if the sheet hasn't changed since the last run
        $csvBody  = $response->body();
        $hash     = md5($csvBody);
        $hashPath = storage_path('app/flex-sheet-hash.txt');

        if (!$dryRun && file_exists($hashPath) && file_get_contents($hashPath) === $hash) {
            $this->info('Sheet unchanged since last run — nothing to do.');
            return 0;
        }

        // 3. Parse CSV — write to temp file so fgetcsv handles quoted newlines correctly
        $tmpPath = storage_path('app/tmp-flex-sheet.csv');
        file_put_contents($tmpPath, $csvBody);
        $rows = [];
        if (($fh = fopen($tmpPath, 'r')) !== false) {
            while (($row = fgetcsv($fh)) !== false) {
                $rows[] = $row;
            }
            fclose($fh);
        }
        @unlink($tmpPath);

        if (empty($rows)) {
            $this->error('No rows parsed from sheet');
            return 1;
        }

        // 4. Find the header row (the one that contains "jukebox")
        $headerIdx = null;
        $headerRow = null;
        foreach ($rows as $i => $row) {
            $lower = array_map(fn($c) => strtolower(trim((string) $c)), $row);
            if (in_array('jukebox', $lower)) {
                $headerIdx = $i;
                $headerRow = $lower;
                break;
            }
        }

        if ($headerIdx === null) {
            $this->error('Could not locate header row — is the sheet accessible?');
            return 1;
        }

        // 5. Map column indices to shows (scoped to this season to avoid matching same-named prior shows)
        $seasonDates = TheaterSeason::datesForSeason(self::SEASON);
        $showCols = [];  // col_index => ['show_id', 'perf_id', 'perf_date', 'label']
        foreach ($headerRow as $col => $cell) {
            foreach (self::SHOW_KEYWORDS as $keyword => $pattern) {
                if (str_contains($cell, $keyword)) {
                    $show = Show::where('name', 'like', $pattern)
                        ->whereHas('performances', fn($q) => $q->whereBetween('date', [$seasonDates['start'], $seasonDates['end']]))
                        ->first();
                    if ($show) {
                        $perf = Performance::where('show_id', $show->id)->orderBy('date')->first();
                        if ($perf) {
                            $showCols[$col] = [
                                'show_id'   => $show->id,
                                'perf_id'   => $perf->id,
                                'perf_date' => $perf->date,
                                'label'     => $show->name,
                            ];
                        }
                    }
                    break;
                }
            }
        }

        if (empty($showCols)) {
            $this->error('Could not map any show columns from the header row');
            return 1;
        }

        $this->info('Shows mapped: ' . implode(', ', array_column($showCols, 'label')));

        // 6. Locate email and total-flex-tickets columns
        $emailCol = array_search('email address', $headerRow);
        $totalCol = null;
        foreach ($headerRow as $i => $cell) {
            if (str_contains($cell, 'total') && str_contains($cell, 'flex')) {
                $totalCol = $i;
                break;
            }
        }

        if ($emailCol === false || $totalCol === null) {
            $this->error('Could not find email or total-flex-tickets column in header');
            return 1;
        }

        // 7. Aggregate sheet usage per email per show
        //    Multiple rows with the same email (e.g. Mucklow's two packages) are summed.
        $sheetUsage = [];  // email => [show_id => total_qty]
        $skippedRows = [];

        foreach (array_slice($rows, $headerIdx + 1) as $row) {
            if ((int) ($row[$totalCol] ?? 0) === 0) {
                continue;
            }

            $raw   = trim((string) ($row[$emailCol] ?? ''));
            $email = strtolower(preg_replace('/\s+/', '', $raw));

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Row has flex data but no usable email — box office staff needs to fill it in
                $name = trim(($row[3] ?? '') . ' ' . ($row[2] ?? ''));
                if (!in_array($name, $skippedRows)) {
                    $skippedRows[] = $name;
                }
                continue;
            }

            foreach ($showCols as $col => $showInfo) {
                $qty = (int) ($row[$col] ?? 0);
                if ($qty > 0) {
                    $sheetUsage[$email][$showInfo['show_id']] =
                        ($sheetUsage[$email][$showInfo['show_id']] ?? 0) + $qty;
                }
            }
        }

        if (!empty($skippedRows)) {
            $this->warn('Rows skipped (no email in sheet): ' . implode(', ', $skippedRows));
        }

        $this->info('Patrons with flex usage in sheet: ' . count($sheetUsage));

        // 8. Compare sheet totals against DB and insert missing sales
        $added      = 0;
        $inSync     = 0;
        $notFound   = 0;
        $warnings   = 0;

        foreach ($sheetUsage as $email => $showUsage) {
            $patron = Patron::where('email', $email)->first();

            if (!$patron) {
                $this->warn("  [SKIP] No patron found for: {$email}");
                Log::warning('sync:flex-sheet patron not found', ['email' => $email]);
                $notFound++;
                continue;
            }

            foreach ($showCols as $showInfo) {
                $showId   = $showInfo['show_id'];
                $label    = $showInfo['label'];
                $sheetQty = $showUsage[$showId] ?? 0;

                $dbQty = TicketSale::where('patron_id', $patron->id)
                    ->where('payment_method_id', self::PM_FLEX)
                    ->whereHas('performance', fn($q) => $q->where('show_id', $showId))
                    ->sum('quantity');

                $diff = $sheetQty - $dbQty;

                if ($diff > 0) {
                    if ($dryRun) {
                        $this->line("  [DRY RUN] +{$diff} flex  {$email}  {$label}");
                    } else {
                        TicketSale::create([
                            'patron_id'         => $patron->id,
                            'payment_method_id' => self::PM_FLEX,
                            'performance_id'    => $showInfo['perf_id'],
                            'quantity'          => $diff,
                            'sold_at'           => $showInfo['perf_date'],
                            'no_show'           => 0,
                        ]);
                        $this->line("  [ADDED] +{$diff} flex  {$email}  {$label}");
                        Log::info('sync:flex-sheet added', ['email' => $email, 'show' => $label, 'qty' => $diff]);
                    }
                    $added += $diff;
                } elseif ($diff < 0) {
                    // DB has more than the sheet — could be a legit website purchase not yet
                    // reflected in the sheet, or a data entry error. Log and skip.
                    $this->warn("  [WARN] {$email} @ {$label}: DB={$dbQty} > sheet={$sheetQty}");
                    Log::warning('sync:flex-sheet DB exceeds sheet', [
                        'email' => $email,
                        'show' => $label,
                        'sheet' => $sheetQty,
                        'db' => $dbQty,
                    ]);
                    $warnings++;
                } else {
                    $inSync++;
                }
            }
        }

        $parts = ["Tickets added: {$added}", "In sync: {$inSync}"];
        if ($notFound)  $parts[] = "Patrons not found: {$notFound}";
        if ($warnings)  $parts[] = "Warnings (DB > sheet): {$warnings}";
        $this->info('Done. ' . implode(', ', $parts));

        Log::info('sync:flex-sheet complete', [
            'added' => $added,
            'in_sync' => $inSync,
            'not_found' => $notFound,
            'warnings' => $warnings,
        ]);

        if (!$dryRun) {
            file_put_contents($hashPath, $hash);
        }

        return 0;
    }
}
