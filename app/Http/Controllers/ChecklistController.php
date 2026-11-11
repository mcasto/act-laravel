<?php

namespace App\Http\Controllers;

use App\Helpers\LocalTime;
use App\Models\ChecklistCheck;
use App\Models\ChecklistItem;
use App\Models\Show;
use App\Services\BoxOfficeChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin → Box Office Checklist: each show's to-dos, checked off by the box
 * office, with overdue ones flagged; plus editing the list and each item's
 * alert rule (see ChecklistItem).
 */
class ChecklistController extends Controller
{
    /** Shows to pick from, newest first, with how many to-dos are overdue. */
    public function shows(): JsonResponse
    {
        $items = ChecklistItem::orderBy('sort_order')->get();
        $today = LocalTime::todayString();

        $shows = Show::with('performances')
            ->whereHas('performances')
            ->get()
            ->sortByDesc(fn (Show $s) => $s->performances->min('date'))
            ->values();

        // The show to open first: the soonest one still playing or coming up.
        $current = $shows->filter(fn (Show $s) => $s->performances->max('date') >= $today)
            ->sortBy(fn (Show $s) => $s->performances->min('date'))
            ->first() ?? $shows->first();

        return response()->json([
            'current_show_id' => $current?->id,
            'shows' => $shows->map(fn (Show $s) => [
                'id'      => $s->id,
                'name'    => $s->name,
                'dates'   => $s->performances->min('date'),
                'overdue' => collect($this->rows($s, $items))->where('overdue', true)->count(),
            ]),
        ]);
    }

    /** One show's checklist. */
    public function show(int $showId): JsonResponse
    {
        $show = Show::with('performances')->findOrFail($showId);
        $items = ChecklistItem::orderBy('sort_order')->get();

        return response()->json([
            'show' => ['id' => $show->id, 'name' => $show->name],
            'rows' => $this->rows($show, $items),
        ]);
    }

    /** Checks or unchecks one to-do. */
    public function check(Request $request, int $showId): JsonResponse
    {
        $show = Show::with('performances')->findOrFail($showId);

        $validated = $request->validate([
            'checklist_item_id' => 'required|integer|exists:checklist_items,id',
            'performance_id'    => ['nullable', 'integer', Rule::in($show->performances->pluck('id'))],
            'checked'           => 'required|boolean',
        ]);

        $item = ChecklistItem::withTrashed()->findOrFail($validated['checklist_item_id']);
        $performanceId = $item->isPerPerformance() ? ($validated['performance_id'] ?? null) : null;

        if ($item->isPerPerformance() && ! $performanceId) {
            return response()->json(['status' => 'error', 'message' => 'Pick a performance.']);
        }

        $key = ['checklist_item_id' => $item->id, 'show_id' => $show->id, 'performance_id' => $performanceId];

        if ($validated['checked']) {
            ChecklistCheck::firstOrCreate($key, [
                'user_id'    => $request->user()?->id,
                'checked_at' => now(),
            ]);
        } else {
            ChecklistCheck::where($key)->delete();
        }

        $row = collect($this->rows($show, collect([$item])))
            ->firstWhere('performance_id', $performanceId);

        return response()->json(['status' => 'success', 'row' => $row]);
    }

    // ── Editing the list ────────────────────────────────────────────────

    public function items(): JsonResponse
    {
        return response()->json([
            'anchors' => ChecklistItem::ANCHORS,
            'items'   => ChecklistItem::orderBy('sort_order')->get()
                ->map(fn (ChecklistItem $i) => $i->toArray() + ['rule' => $i->ruleText()]),
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $item = ChecklistItem::create($this->validated($request) + [
            'sort_order' => (int) ChecklistItem::max('sort_order') + 1,
        ]);

        return response()->json(['status' => 'success', 'item' => $item]);
    }

    public function updateItem(Request $request, int $id): JsonResponse
    {
        $item = ChecklistItem::findOrFail($id);
        $item->update($this->validated($request));

        return response()->json(['status' => 'success', 'item' => $item]);
    }

    /** Removed from the list; shows already checked keep their history. */
    public function destroyItem(int $id): JsonResponse
    {
        ChecklistItem::findOrFail($id)->delete();

        return response()->json(['status' => 'success']);
    }

    /** New order, as a list of item ids. */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        foreach ($validated['ids'] as $i => $id) {
            ChecklistItem::whereKey($id)->update(['sort_order' => $i + 1]);
        }

        return response()->json(['status' => 'success']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label'          => 'required|string|max:255',
            'notes'          => 'nullable|string|max:2000',
            'anchor'         => ['required', Rule::in(array_keys(ChecklistItem::ANCHORS))],
            'offset_minutes' => 'required|integer|between:-525600,525600',
        ]);
    }

    private function rows(Show $show, $items): array
    {
        return BoxOfficeChecklist::rows($show, $items);
    }
}
