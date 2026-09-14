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
        Schema::table('comp_tickets', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable();
            $table->unique(['show_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comp_tickets', function (Blueprint $table) {
            $table->dropUnique(['show_id', 'number']);
            $table->dropColumn('number');
        });
    }
};
