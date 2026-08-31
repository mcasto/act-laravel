<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the permission_levels FK with a plain "section" string —
     * sections are now whatever the frontend's admin routes say they are
     * (dynamic, no DB-side list to keep in sync). Existing rows are just
     * seeded placeholder data (blanket "full" grants), safe to clear.
     */
    public function up(): void
    {
        DB::table('user_permissions')->truncate();

        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropForeign(['permission_level_id']);
            $table->dropColumn('permission_level_id');
            $table->string('section')->after('user_id');
            $table->unique(['user_id', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'section']);
            $table->dropColumn('section');
            $table->foreignId('permission_level_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
