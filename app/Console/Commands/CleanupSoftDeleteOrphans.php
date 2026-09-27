<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup for child rows left live under a soft-deleted parent,
 * from before the models cascaded soft deletes themselves (see the
 * deleting hooks on Show, Audition, Course, AngelLevel, TicketSale).
 * Reports only unless --fix is passed. Children are soft-deleted with
 * their parent's own deleted_at so it's clear what they went with;
 * tickets have no soft-delete column, so they're removed outright.
 * Ticket sales under a deleted performance are only reported — those are
 * financial records and need a person to decide what happens to them.
 */
class CleanupSoftDeleteOrphans extends Command
{
    protected $signature = 'data:cleanup-soft-delete-orphans {--fix : Apply the cleanup instead of just reporting}';
    protected $description = 'Find (and with --fix, delete) rows left live under a soft-deleted parent';

    /**
     * [child table, foreign key, parent table]. Order matters: a parent
     * cleaned up earlier in the list (e.g. auditions under a deleted show)
     * then has its own children picked up further down.
     */
    private const CASCADES = [
        ['performances', 'show_id', 'shows'],
        ['auditions', 'show_id', 'shows'],
        ['gallery_images', 'show_id', 'shows'],
        ['comp_tickets', 'show_id', 'shows'],
        ['audition_sessions', 'audition_id', 'auditions'],
        ['audition_roles', 'audition_id', 'auditions'],
        ['course_sessions', 'course_id', 'courses'],
        ['course_contacts', 'course_id', 'courses'],
        ['angels', 'angel_level_id', 'angel_levels'],
    ];

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');

        DB::transaction(function () use ($fix) {
            foreach (self::CASCADES as [$child, $fk, $parent]) {
                $orphans = $this->liveChildrenOfDeleted($child, $fk, $parent);
                $this->line(sprintf('%-18s under deleted %-13s %d%s',
                    $child, $parent, $orphans->count(),
                    $orphans->isEmpty() ? '' : ' (ids ' . $orphans->pluck('id')->implode(', ') . ')'));

                if ($fix) {
                    foreach ($orphans as $row) {
                        DB::table($child)->where('id', $row->id)->update(['deleted_at' => $row->parent_deleted_at]);
                    }
                }
            }

            $tickets = DB::table('tickets as c')
                ->join('ticket_sales as p', 'p.id', '=', 'c.ticket_sale_id')
                ->whereNotNull('p.deleted_at')
                ->pluck('c.id');
            $this->line(sprintf('%-18s under deleted %-13s %d%s',
                'tickets', 'ticket_sales', $tickets->count(),
                $tickets->isEmpty() ? '' : ' (ids ' . $tickets->implode(', ') . ')'));
            if ($fix) {
                DB::table('tickets')->whereIn('id', $tickets)->delete();
            }

            $sales = $this->liveChildrenOfDeleted('ticket_sales', 'performance_id', 'performances');
            if ($sales->isNotEmpty()) {
                $this->warn("ticket_sales under a deleted performance — NOT changed, review by hand: ids "
                    . $sales->pluck('id')->implode(', '));
            }
        });

        $this->info($fix ? 'Cleanup applied.' : 'Report only — rerun with --fix to apply.');

        return 0;
    }

    private function liveChildrenOfDeleted(string $child, string $fk, string $parent)
    {
        return DB::table("{$child} as c")
            ->join("{$parent} as p", 'p.id', '=', "c.{$fk}")
            ->whereNotNull('p.deleted_at')
            ->whereNull('c.deleted_at')
            ->get(['c.id', 'p.deleted_at as parent_deleted_at']);
    }
}
