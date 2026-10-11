<?php

use App\Helpers\TheaterSeason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A few current-season angels were entered before angels.patron_id
     * existed (2026_08_27_165100), so they were never linked to a patron —
     * which hides their benefits on the ticket sale form/print sheet and
     * their email on the Our Angels edit dialog. Links each one to its
     * patron by exact first + last name, but only when exactly one patron
     * matches; anything ambiguous is left alone for a manual fix.
     *
     * Founding-angel status is per-patron now (see
     * AngelController::resolveFoundingAngel()), so a linked record's
     * founding flag carries over to the patron too.
     */
    public function up(): void
    {
        $angels = DB::table('angels')
            ->where('season', TheaterSeason::currentString())
            ->whereNull('patron_id')
            ->whereNull('deleted_at')
            ->get();

        foreach ($angels as $angel) {
            $patronIds = DB::table('patrons')
                ->where('first_name', $angel->first_name)
                ->where('last_name', $angel->last_name)
                ->pluck('id');

            if ($patronIds->count() !== 1) {
                continue;
            }

            DB::table('angels')->where('id', $angel->id)->update(['patron_id' => $patronIds->first()]);

            if ($angel->founding_angel) {
                DB::table('patrons')->where('id', $patronIds->first())->update(['founding_angel' => true]);
            }
        }
    }

    /**
     * Data fix — not reversible (the original nulls aren't tracked, and
     * unlinking would only bring the problem back).
     */
    public function down(): void
    {
    }
};
