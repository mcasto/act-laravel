<?php

namespace App\Models;

use App\Helpers\LocalTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One box office to-do every show gets, with its alert rule: due at
 * `anchor` + `offset_minutes` (negative = before). See the
 * create_box_office_checklist_tables migration.
 */
class ChecklistItem extends Model
{
    use SoftDeletes;

    public const ANCHORS = [
        'sales_start'       => 'ticket sales start',
        'first_performance' => 'the first performance',
        'each_performance'  => 'each performance',
        'last_performance'  => 'the last performance',
    ];

    protected $fillable = ['label', 'notes', 'anchor', 'offset_minutes', 'sort_order'];

    protected $casts = ['offset_minutes' => 'integer', 'sort_order' => 'integer'];

    public function isPerPerformance(): bool
    {
        return $this->anchor === 'each_performance';
    }

    /**
     * When it's due, in local time — null when the show doesn't have the
     * anchor yet (e.g. no performances).
     */
    public function dueAt(Show $show, ?Performance $performance = null): ?Carbon
    {
        $zone = LocalTime::zone();
        $performances = $show->performances->sortBy(fn ($p) => $p->date . ' ' . $p->start_time);
        $at = fn (?Performance $p) => $p ? Carbon::parse("{$p->date} {$p->start_time}", $zone) : null;

        $anchor = match ($this->anchor) {
            'sales_start'       => $show->ticket_sales_start ? Carbon::parse($show->ticket_sales_start, $zone)->startOfDay() : null,
            'first_performance' => $at($performances->first()),
            'last_performance'  => $at($performances->last()),
            'each_performance'  => $at($performance),
            default             => null,
        };

        return $anchor?->copy()->addMinutes($this->offset_minutes);
    }

    /** e.g. "3 hours before each performance", "7 days after the last performance". */
    public function ruleText(): string
    {
        $m = abs($this->offset_minutes);
        $anchor = self::ANCHORS[$this->anchor] ?? $this->anchor;

        if ($m === 0) {
            return 'At ' . $anchor;
        }

        [$n, $unit] = $m % 1440 === 0 ? [$m / 1440, 'day'] : ($m % 60 === 0 ? [$m / 60, 'hour'] : [$m, 'minute']);

        return "{$n} {$unit}" . ($n == 1 ? '' : 's') . ($this->offset_minutes < 0 ? ' before ' : ' after ') . $anchor;
    }
}
