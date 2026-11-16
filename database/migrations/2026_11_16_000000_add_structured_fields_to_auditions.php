<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured audition form (audition-form.todo, Phase 1). The form's data
 * generates the audition's freeform HTML; the HTML stays the published page.
 * Null in location / registration_note / contacts means "use the default"
 * (ACT's address, the standard note, the show's director).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditions', function (Blueprint $table) {
            $table->text('what_to_expect')->nullable()->after('end_display_date');
            $table->text('location')->nullable()->after('what_to_expect');
            $table->string('registration_url', 500)->nullable()->after('location');
            $table->string('registration_note', 500)->nullable()->after('registration_url');
            $table->string('script_url', 500)->nullable()->after('registration_note');
            $table->dateTime('rehearsal_start')->nullable()->after('script_url');
            $table->text('rehearsal_schedule')->nullable()->after('rehearsal_start');
            $table->json('contacts')->nullable()->after('rehearsal_schedule');
        });

        Schema::table('audition_sessions', function (Blueprint $table) {
            $table->time('end_time')->nullable()->after('session');
            $table->string('note', 500)->nullable()->after('end_time');
            $table->boolean('is_callback')->default(false)->after('note');
        });

        Schema::table('audition_roles', function (Blueprint $table) {
            $table->string('gender_age')->nullable()->after('name');
            $table->longText('notes')->nullable()->change();
            $table->unsignedInteger('sort_order')->default(0)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('audition_roles', function (Blueprint $table) {
            $table->dropColumn(['gender_age', 'sort_order']);
        });

        Schema::table('audition_sessions', function (Blueprint $table) {
            $table->dropColumn(['end_time', 'note', 'is_callback']);
        });

        Schema::table('auditions', function (Blueprint $table) {
            $table->dropColumn([
                'what_to_expect', 'location', 'registration_url', 'registration_note',
                'script_url', 'rehearsal_start', 'rehearsal_schedule', 'contacts',
            ]);
        });
    }
};
