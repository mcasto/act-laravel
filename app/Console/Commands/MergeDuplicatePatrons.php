<?php

namespace App\Console\Commands;

use App\Models\Patron;
use App\Services\PatronMerger;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Merges every set of patron records that share a full name into one — for
 * cleaning up duplicates once patron_emails exists (run after migrating).
 *
 * Which record is kept, in order: most ticket sales, most other records,
 * has a real email address (not blank or a "no-email+…" stand-in),
 * oldest. A merged record's real address becomes the main one when the
 * record kept has none. The rest are merged into it with
 * PatronMerger (same as the Patrons page's Merge action): their purchases
 * and other records move over, their addresses become extra addresses,
 * and they're soft-deleted.
 *
 * Names that look alike but aren't one person are skipped: the built-in
 * placeholders below, plus anything passed with --except.
 */
class MergeDuplicatePatrons extends Command
{
    protected $signature = 'patrons:merge-duplicates
        {--dry-run : Show what would be merged without changing anything}
        {--force : Merge without asking for confirmation}
        {--except=* : A full name to leave alone (repeatable), e.g. --except="Jane Smith"}';

    protected $description = 'Merge patron records that share a full name into one record each';

    /** Placeholder names on records that aren't one person. */
    private const SKIP_NAMES = ['john doe'];

    public function handle(PatronMerger $merger): int
    {
        $skip = collect(self::SKIP_NAMES)
            ->merge(collect($this->option('except'))->map(fn ($n) => $this->key($n)))
            ->unique();

        $groups = $this->duplicateGroups()->reject(fn ($_, $name) => $skip->contains($name));

        if ($groups->isEmpty()) {
            $this->info('No duplicate patrons to merge.');
            return self::SUCCESS;
        }

        $plan = $groups->map(function (Collection $patrons) use ($merger) {
            $ranked = $patrons
                ->map(fn (Patron $p) => ['patron' => $p, 'summary' => $merger->summary($p)])
                ->sort(fn ($a, $b) => $this->rank($b) <=> $this->rank($a))
                ->values();

            return ['keep' => $ranked->first(), 'merge' => $ranked->slice(1)->values()];
        });

        foreach ($plan as $name => $step) {
            $this->newLine();
            $this->line("<options=bold>{$step['keep']['patron']->first_name} {$step['keep']['patron']->last_name}</>");
            $this->line('  keep   ' . $this->describe($step['keep']));
            foreach ($step['merge'] as $dup) {
                $this->line('  merge  ' . $this->describe($dup));
            }
        }

        $records = $plan->sum(fn ($step) => $step['merge']->count());
        $this->newLine();
        $this->info("{$plan->count()} people, {$records} duplicate records to merge."
            . ($skip->isNotEmpty() ? ' Skipped: ' . $skip->map(fn ($n) => ucwords($n))->implode(', ') . '.' : ''));

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing changed.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Merge these now?')) {
            $this->comment('Nothing changed.');
            return self::SUCCESS;
        }

        foreach ($plan as $step) {
            $keep = $step['keep']['patron'];
            foreach ($step['merge'] as $dup) {
                $merger->merge($keep, $dup['patron']);
                $keep->refresh();
            }
        }

        $this->info("Merged {$records} duplicate records.");

        return self::SUCCESS;
    }

    /** Lowercase, trimmed, single-spaced full name. */
    private function key(?string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $name)));
    }

    /** @return Collection<string, Collection<int, Patron>> keyed by name */
    private function duplicateGroups(): Collection
    {
        return Patron::orderBy('id')->get()
            ->groupBy(fn (Patron $p) => $this->key("{$p->first_name} {$p->last_name}"))
            ->reject(fn ($patrons, $name) => $name === '' || $patrons->count() < 2);
    }

    /** Higher = better record to keep. */
    private function rank(array $entry): array
    {
        $counts = $entry['summary']['counts'];
        $patron = $entry['patron'];

        return [
            (int) ($counts['ticket_sales'] ?? 0),
            (int) collect($counts)->except('ticket_sales')->sum(),
            Patron::isRealEmail($patron->email) ? 1 : 0,
            -$patron->id,
        ];
    }

    private function describe(array $entry): string
    {
        $p = $entry['patron'];
        $counts = collect($entry['summary']['counts'])
            ->map(fn ($n, $table) => "{$n} " . str_replace('_', ' ', $table))
            ->implode(', ');

        return sprintf('#%-5d %-38s %s', $p->id, $p->email ?: '(no email)', $counts ?: 'no records');
    }
}
