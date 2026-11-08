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
        // Show-scoped access for a show's production team (director, AD,
        // stage manager…) via the desktop director app. One row per person
        // per show; any number of people per show. `role` is a free-text
        // label for display/history only — every role gets the same access.
        // starts_at/ends_at are what's enforced (null = open-ended); they're
        // pre-filled from ShowAccessGrant::defaultWindow() in the UI but never
        // re-derived on save. Expired grants are kept, never deleted.
        Schema::create('show_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'show_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('show_access_grants');
    }
};
