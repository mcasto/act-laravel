<?php

use App\Helpers\LocalTime;
use App\Models\ShowAccessGrant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-time cleanup. Removing someone from a show's production team used
     * to keep the row and set ends_at to the day before the removal (local
     * time); removal now deletes the row instead. Delete the rows the old
     * behavior left behind — identified by ends_at being exactly the local
     * day before the row was last updated — so they stop showing as "ended".
     * Grants that simply ran out are untouched (their last update wasn't
     * the day after their end date).
     */
    public function up(): void
    {
        ShowAccessGrant::whereNotNull('ends_at')->get()
            ->filter(fn (ShowAccessGrant $g) => $g->updated_at
                && $g->updated_at->gt($g->created_at)
                && $g->ends_at->toDateString() === $g->updated_at->copy()
                    ->setTimezone(LocalTime::zone())->subDay()->toDateString())
            // Model delete, so the show's public director name re-syncs.
            ->each->delete();
    }

    public function down(): void
    {
        // Deleted rows can't be restored.
    }
};
