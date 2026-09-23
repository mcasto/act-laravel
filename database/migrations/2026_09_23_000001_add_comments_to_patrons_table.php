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
            // Free-form, admin-only notes (e.g. "usually ~15 min late, hold
            // their seat if prepaid") — never shown to the patron, never
            // used in any automated logic, just a place for box office to
            // record institutional knowledge about a patron.
            $table->longText('comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrons', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
    }
};
