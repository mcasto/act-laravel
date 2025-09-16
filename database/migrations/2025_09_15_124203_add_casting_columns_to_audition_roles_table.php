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
        Schema::table('audition_roles', function (Blueprint $table) {
            $table->string('cast')->nullable()->after('side');
            $table->string('cast_phone')->nullable()->after('cast');
            $table->string('cast_email')->nullable()->after('cast_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
