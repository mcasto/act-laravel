<?php

namespace App\Services;

use App\Helpers\LocalTime;
use App\Models\ChecklistCheck;
use App\Models\ChecklistItem;
use App\Models\Show;

/**
 * The box office checklist for a show, with each to-do's due time and
 * whether it's overdue — shared by the Box Office Checklist page
 * (ChecklistController) and the overdue email (checklist:notify-overdue).
 */
class BoxOfficeChecklist
{
    /**
     * One row per to-do: per show, or per performance for "each
     * performance" items. Removed items still show on a show where they
     * were checked off, so its history stays complete.
     */
    public static function rows(Show $show, $items): array
    {
        $checks = ChecklistCheck::with('user:id,name')
            ->where('show_id', $show->id)
            ->get()
            ->keyBy(fn ($c) => $c->checklist_item_id . ':' . ($c->performance_id ?? ''));

        $removedButChecked = ChecklistItem::onlyTrashed()
            ->whereIn('id', $checks->pluck('checklist_item_id'))
            ->get();

        $now = LocalTime::now();
        $performances = $show->performances->sortBy(fn ($p) => $p->date . ' ' . $p->start_time);
        $rows = [];

        foreach ($items->concat($removedButChecked) as $item) {
            $targets = $item->isPerPerformance() ? $performances->all() : [null];

            foreach ($targets as $performance) {
                $check = $checks->get($item->id . ':' . ($performance?->id ?? ''));
                if ($item->trashed() && ! $check) {
                    continue;
                }

                $due = $item->dueAt($show, $performance);

                $rows[] = [
                    'checklist_item_id' => $item->id,
                    'performance_id'    => $performance?->id,
                    'label'             => $item->label,
                    'notes'             => $item->notes,
                    'rule'              => $item->ruleText(),
                    'removed'           => $item->trashed(),
                    'performance'       => $performance ? "{$performance->formatted_date} {$performance->formatted_time}" : null,
                    'due_at'            => $due?->toIso8601String(),
                    'due_label'         => $due?->format('D, M j, Y g:i A'),
                    'checked'           => (bool) $check,
                    'checked_at'        => $check?->checked_at?->setTimezone(LocalTime::zone())->format('M j, Y g:i A'),
                    'checked_by'        => $check?->user?->name,
                    'overdue'           => ! $check && $due !== null && $due->lte($now),
                    'due_soon'          => ! $check && $due !== null && $due->gt($now) && $due->lte($now->copy()->addDay()),
                ];
            }
        }

        return $rows;
    }
}
