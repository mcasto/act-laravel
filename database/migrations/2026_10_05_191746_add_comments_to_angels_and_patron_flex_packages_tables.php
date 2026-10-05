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
        // Admin-only notes for oddities on a single record (e.g. one patron
        // paying on another's behalf). Never sent to public endpoints — see
        // AngelLevelController::index().
        Schema::table('angels', function (Blueprint $table) {
            $table->text('comments')->nullable();
        });

        Schema::table('patron_flex_packages', function (Blueprint $table) {
            $table->text('comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('angels', function (Blueprint $table) {
            $table->dropColumn('comments');
        });

        Schema::table('patron_flex_packages', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
    }
};
