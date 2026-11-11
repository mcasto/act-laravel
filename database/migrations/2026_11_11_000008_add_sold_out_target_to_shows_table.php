<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional per-show sold-out target. A performance's own target wins,
     * then the show's, then the global Site Config default (see
     * Performance::effectiveSoldOutTarget()). Null = use the default.
     */
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->unsignedSmallInteger('sold_out_target')->nullable()->after('ticket_price');
        });
    }

    public function down(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->dropColumn('sold_out_target'));
    }
};
