<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * performances.sold_out_target becomes an optional per-performance
     * override: null = use the global Default Sold Out Target from Site
     * Config (see Performance::effectiveSoldOutTarget()).
     *
     * Every existing 50 came from the column default / a hard-coded 50 on
     * save — there was no way to set it in the admin — so those become null
     * and follow the global target. Any other value is kept as an override.
     */
    public function up(): void
    {
        Schema::table('performances', function (Blueprint $table) {
            $table->integer('sold_out_target')->nullable()->default(null)->change();
        });

        DB::table('performances')->where('sold_out_target', 50)->update(['sold_out_target' => null]);
    }

    public function down(): void
    {
        DB::table('performances')->whereNull('sold_out_target')->update(['sold_out_target' => 50]);

        Schema::table('performances', function (Blueprint $table) {
            $table->integer('sold_out_target')->default(50)->nullable(false)->change();
        });
    }
};
