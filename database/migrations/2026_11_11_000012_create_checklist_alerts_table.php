<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Overdue box office to-dos already emailed about (checklist:notify-
     * overdue), so each one is emailed once. due_at is kept: if an item's
     * rule changes its due time, it can be emailed again for the new time.
     */
    public function up(): void
    {
        Schema::create('checklist_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('due_at');
            $table->timestamp('notified_at');
            $table->timestamps();
            $table->unique(['checklist_item_id', 'show_id', 'performance_id', 'due_at'], 'checklist_alerts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_alerts');
    }
};
