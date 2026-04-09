<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('angels', function (Blueprint $table) {
            $table->string('season')->nullable()->after('benefit');
        });

        Schema::table('angels', function (Blueprint $table) {
            $table->dropColumn(['year_start', 'year_end']);
        });
    }

    public function down(): void
    {
        Schema::table('angels', function (Blueprint $table) {
            $table->integer('year_start')->nullable()->after('benefit');
            $table->integer('year_end')->nullable()->after('year_start');
        });

        Schema::table('angels', function (Blueprint $table) {
            $table->dropColumn('season');
        });
    }
};
