<?php

namespace App\Services;

use App\Models\Patron;
use App\Models\PatronEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Merges a duplicate patron record into another — the single source for
 * the Patrons page's Merge action (PatronController::merge()) and the
 * patrons:merge-duplicates command.
 *
 * Everything pointing at the duplicate (any table with a patron_id: ticket
 * sales, Angel records, Flex packages and pending requests, donations,
 * class enrollments, Fixr records, reminders, email addresses, …) moves to
 * the patron kept; blank details there are filled from the duplicate,
 * never overwritten; and the duplicate is soft-deleted.
 */
class PatronMerger
{
    /** @var array<int, string>|null */
    private ?array $linkedTables = null;

    public function merge(Patron $into, Patron $from): array
    {
        if ($into->id === $from->id) {
            throw new \InvalidArgumentException("Can't merge a patron into itself.");
        }

        $summary = $this->summary($from);

        DB::transaction(function () use ($from, $into) {
            foreach ($this->linkedTables() as $table) {
                DB::table($table)->where('patron_id', $from->id)->update(['patron_id' => $into->id]);
            }

            // Its main address too, if it was never listed.
            $fromMain = Patron::normalizeEmail($from->email);
            if (filter_var($fromMain, FILTER_VALIDATE_EMAIL) && ! PatronEmail::where('email', $fromMain)->exists()) {
                $into->emails()->create(['email' => $fromMain]);
            }

            // Fill in what the kept patron is missing; never overwrite. A
            // main email that isn't a real address (e.g. a name typed into
            // the field) counts as missing.
            $fill = [];
            foreach (['phone', 'comments'] as $field) {
                if (blank($into->{$field}) && filled($from->{$field})) {
                    $fill[$field] = $from->{$field};
                }
            }
            // A main address that's missing, invalid or a "no-email+…"
            // stand-in is replaced by a real one when there is one.
            if (! Patron::isRealEmail($into->email)) {
                $replacement = $into->emails()->pluck('email')->first(fn ($e) => Patron::isRealEmail($e));
                if ($replacement) {
                    $fill['email'] = $replacement;
                }
            }
            $fill['front_row'] = max((int) $into->front_row, (int) $from->front_row);
            $fill['founding_angel'] = $into->founding_angel || $from->founding_angel;
            $into->update($fill);

            $from->delete();
        });

        ChangeLogger::record(
            "Merged patron #{$from->id} {$from->first_name} {$from->last_name} into #{$into->id} {$into->first_name} {$into->last_name}",
            ['moved' => $summary['counts']]
        );

        return $summary;
    }

    /**
     * Who the patron is and what a merge would move — live records only
     * (deleted ones move too, but aren't worth listing).
     */
    public function summary(Patron $patron): array
    {
        $counts = collect($this->linkedTables())
            ->reject(fn ($table) => $table === 'patron_emails')
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)
                ->where('patron_id', $patron->id)
                ->when(Schema::hasColumn($table, 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->count()])
            ->filter();

        return [
            'id'         => $patron->id,
            'first_name' => $patron->first_name,
            'last_name'  => $patron->last_name,
            'email'      => $patron->email,
            'phone'      => $patron->phone,
            'emails'     => $patron->emails()->pluck('email'),
            'counts'     => $counts,
        ];
    }

    /** Every table with a patron_id column (patron_emails included). */
    public function linkedTables(): array
    {
        return $this->linkedTables ??= collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn ($table) => $table === 'patrons')
            ->filter(fn ($table) => Schema::hasColumn($table, 'patron_id'))
            ->values()
            ->all();
    }
}
