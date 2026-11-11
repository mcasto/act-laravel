<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Box office checklist (Admin → Box Office Checklist).
     *
     * checklist_items — the to-do list every show gets, each with an alert
     * rule: due at an anchor (ticket sales start, first / each / last
     * performance) plus an offset in minutes (negative = before). An
     * "each performance" item is a separate to-do per performance. Removed
     * items are soft-deleted so past shows keep their history.
     *
     * checklist_checks — what's been checked off, per show (and per
     * performance for "each performance" items), by whom and when.
     */
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->text('notes')->nullable();
            $table->string('anchor', 32);
            $table->integer('offset_minutes')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('checklist_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->unique(['checklist_item_id', 'show_id', 'performance_id'], 'checklist_checks_unique');
        });

        // A starter list — edit it on the page.
        $day = 24 * 60;
        $now = now();
        $items = [
            ['Ticket price and sold-out target set for the show', 'sales_start', -7 * $day],
            ['Fixr (credit card) link added to every performance', 'sales_start', -3 * $day],
            ['Purchase page checked: dates, prices and payment options', 'sales_start', -1 * $day],
            ['Payment reminders sent for unpaid reservations', 'first_performance', -2 * $day],
            ['Door sheet exported and printed', 'each_performance', -3 * 60],
            ['No-shows, walk-ins and door payments entered', 'each_performance', 1 * $day],
            ['Board reports exported', 'last_performance', 7 * $day],
        ];
        foreach ($items as $i => [$label, $anchor, $offset]) {
            DB::table('checklist_items')->insert([
                'label' => $label, 'anchor' => $anchor, 'offset_minutes' => $offset,
                'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_checks');
        Schema::dropIfExists('checklist_items');
    }
};
