<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `special_seating` used to be a single integer (reserved party seat
     * count, e.g. an Angel level's reserved seating). Splitting that into
     * a dedicated `front_row` count and repurposing `special_seating`
     * itself as free-text seating notes (e.g. "aisle seat") — distinct
     * from `patrons.front_row`, which is one patron's own standing
     * accessibility need, not tied to any single sale.
     */
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->unsignedTinyInteger('front_row')->default(0)->after('special_seating');
        });

        // Carry the old numeric meaning over before special_seating is
        // dropped and rebuilt as text below.
        DB::table('ticket_sales')->update(['front_row' => DB::raw('special_seating')]);

        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('special_seating');
        });

        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->text('special_seating')->nullable()->after('front_row');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('special_seating');
        });

        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->unsignedTinyInteger('special_seating')->default(0)->after('front_row');
        });

        DB::table('ticket_sales')->update(['special_seating' => DB::raw('front_row')]);

        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('front_row');
        });
    }
};
