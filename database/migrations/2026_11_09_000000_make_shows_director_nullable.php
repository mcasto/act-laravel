<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * shows.director is no longer entered on the show form — it's kept in
     * sync from the show's Production Team (grants with the Director role),
     * so a newly created show has no director until one is added there.
     */
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->string('director')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->string('director')->nullable(false)->change();
        });
    }
};
