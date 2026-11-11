<?php

namespace App\Console\Commands;

use App\Helpers\LocalTime;
use App\Mail\ChecklistOverdueMailer;
use App\Models\ChecklistAlert;
use App\Models\ChecklistItem;
use App\Models\Show;
use App\Services\BoxOfficeChecklist;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the box office (config mail.box_office, MAIL_BOX_OFFICE_ADDRESS)
 * about checklist to-dos that have gone overdue — one email per run
 * listing them all, each to-do emailed once. Only to-dos that went overdue
 * in the last day are emailed, so old shows that were never checked off
 * (and items whose rule is changed to a time long past) don't flood the
 * inbox; those still show red on the page.
 */
class NotifyOverdueChecklist extends Command
{
    protected $signature = 'checklist:notify-overdue {--dry-run : List what would be emailed without sending}';

    protected $description = 'Email the box office about checklist items that just went overdue';

    private const LOOKBACK_HOURS = 24;

    public function handle(): int
    {
        $to = config('mail.box_office.address');
        $now = LocalTime::now();
        $since = $now->copy()->subHours(self::LOOKBACK_HOURS);
        $items = ChecklistItem::orderBy('sort_order')->get();

        // Shows with a performance within the window that matters: the
        // latest rule offset could reach back or forward a long way, so just
        // take shows whose run is within a year of today.
        $shows = Show::with('performances')
            ->whereHas('performances', fn ($q) => $q->whereBetween('date', [
                $now->copy()->subYear()->toDateString(),
                $now->copy()->addYear()->toDateString(),
            ]))
            ->get();

        $new = [];
        foreach ($shows as $show) {
            foreach (BoxOfficeChecklist::rows($show, $items) as $row) {
                if (! $row['overdue'] || $row['removed']) {
                    continue;
                }

                $due = Carbon::parse($row['due_at']);
                if ($due->lt($since)) {
                    continue;
                }

                $key = [
                    'checklist_item_id' => $row['checklist_item_id'],
                    'show_id'           => $show->id,
                    'performance_id'    => $row['performance_id'],
                    'due_at'            => $due->copy()->utc(),
                ];
                if (ChecklistAlert::where($key)->exists()) {
                    continue;
                }

                $new[] = ['key' => $key, 'row' => $row + ['show' => $show->name]];
            }
        }

        if (! $new) {
            return self::SUCCESS;
        }

        foreach ($new as $n) {
            $this->line("Overdue: {$n['row']['label']} — {$n['row']['show']}"
                . ($n['row']['performance'] ? " ({$n['row']['performance']})" : '')
                . " — due {$n['row']['due_label']}");
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing sent.');
            return self::SUCCESS;
        }

        if (! $to) {
            $this->warn('MAIL_BOX_OFFICE_ADDRESS is not set — not emailed.');
            return self::SUCCESS;
        }

        Mail::to($to)->send(new ChecklistOverdueMailer(
            collect($new)->pluck('row')->sortBy('due_at')->values()->all(),
            url('/admin/box-office-checklist'),
        ));

        foreach ($new as $n) {
            ChecklistAlert::create($n['key'] + ['notified_at' => now()]);
        }

        $this->info('Emailed ' . count($new) . " overdue item(s) to {$to}.");

        return self::SUCCESS;
    }
}
