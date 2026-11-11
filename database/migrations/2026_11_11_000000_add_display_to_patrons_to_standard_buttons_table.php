<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When off, the button is left out of every patron-facing purchase
     * list (tickets, Flex, courses, Angels, Support Us) — see
     * StandardButton::forPatrons(). Defaults on so every existing button
     * keeps showing.
     */
    public function up(): void
    {
        Schema::table('standard_buttons', function (Blueprint $table) {
            $table->boolean('display_to_patrons')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('standard_buttons', function (Blueprint $table) {
            $table->dropColumn('display_to_patrons');
        });
    }
};
