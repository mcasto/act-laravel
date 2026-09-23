<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patrons', function (Blueprint $table) {
            // Number of front-row seats this patron needs reserved (e.g. a
            // wheelchair user taking up 2 seats plus a caretaker, or a
            // vision-impaired patron plus their caretaker) — not tied to
            // any single show/sale, a standing accommodation on the patron.
            $table->unsignedTinyInteger('front_row')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrons', function (Blueprint $table) {
            $table->dropColumn('front_row');
        });
    }
};
