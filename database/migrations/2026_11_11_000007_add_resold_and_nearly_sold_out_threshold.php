<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ticket_sales.resold — how many of a sale's cancelled tickets were put
     * back on sale (never more than cancelled). A cancelled seat that isn't
     * resold still counts against the performance's seats.
     *
     * site_configs.nearly_sold_out_threshold — when a patron picks a
     * performance with this many seats left or fewer, they're warned and
     * (for methods where they enter a quantity) capped at the seats left.
     */
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->unsignedSmallInteger('resold')->default(0)->after('cancelled_returned');
        });

        Schema::table('site_configs', function (Blueprint $table) {
            $table->unsignedSmallInteger('nearly_sold_out_threshold')->default(10)->after('sold_out_target');
        });

        // The cached config row predates the new column.
        Cache::forget('site-config');
    }

    public function down(): void
    {
        Schema::table('ticket_sales', fn (Blueprint $table) => $table->dropColumn('resold'));
        Schema::table('site_configs', fn (Blueprint $table) => $table->dropColumn('nearly_sold_out_threshold'));
    }
};
