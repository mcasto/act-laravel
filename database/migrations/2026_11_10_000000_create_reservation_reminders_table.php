<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reservation_reminders — one row per reminder per patron per
     * performance, written by reminders:send whether the email was actually
     * sent or only logged (RESERVATION_REMINDERS_SEND off). The same-day
     * batch uses it to skip anyone already reminded for that performance.
     *
     * performances.same_day_reminders_at — set once the same-day (4 hours
     * before curtain) batch has run for a performance, so it fires exactly
     * once and later purchases don't trigger it again.
     */
    public function up(): void
    {
        Schema::create('reservation_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->constrained();
            $table->foreignId('patron_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('batch', 20);   // day_before | same_day
            $table->unsignedInteger('quantity');
            $table->string('status', 10);  // logged | sent | failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['performance_id', 'email', 'batch']);
        });

        Schema::table('performances', function (Blueprint $table) {
            $table->timestamp('same_day_reminders_at')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('performances', function (Blueprint $table) {
            $table->dropColumn('same_day_reminders_at');
        });

        Schema::dropIfExists('reservation_reminders');
    }
};
