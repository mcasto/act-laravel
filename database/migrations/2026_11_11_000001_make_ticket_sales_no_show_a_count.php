<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * no_show becomes the number of people in the party who didn't show
     * (0..quantity), not a yes/no for the whole purchase — one of a party
     * of 4 can miss the show while the other 3 come.
     *
     * Existing "yes" values meant the whole party was a no-show, so they
     * become the sale's quantity to keep that meaning.
     */
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->unsignedSmallInteger('no_show')->default(0)->change();
        });

        DB::table('ticket_sales')
            ->where('no_show', 1)
            ->where('quantity', '>', 1)
            ->update(['no_show' => DB::raw('quantity')]);
    }

    public function down(): void
    {
        DB::table('ticket_sales')->where('no_show', '>', 1)->update(['no_show' => 1]);

        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->boolean('no_show')->default(false)->change();
        });
    }
};
