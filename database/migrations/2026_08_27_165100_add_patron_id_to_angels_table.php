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
        Schema::table('angels', function (Blueprint $table) {
            $table->foreignId('patron_id')->nullable()->after('recognition_name')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('angels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patron_id');
        });
    }
};
