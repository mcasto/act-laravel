<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When on, the audition is viewable at /audition/preview regardless of
     * its display dates — for checking the page before it goes live.
     */
    public function up(): void
    {
        Schema::table('auditions', function (Blueprint $table) {
            $table->boolean('preview')->default(false)->after('end_display_date');
        });
    }

    public function down(): void
    {
        Schema::table('auditions', function (Blueprint $table) {
            $table->dropColumn('preview');
        });
    }
};
