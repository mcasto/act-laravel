<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * guest_list (a free-text field for groups larger than 2) is fully
     * superseded by the per-ticket "Tickets" name fields — every guest now
     * gets their own named ticket instead of one freeform note on the sale.
     */
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('guest_list');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->text('guest_list')->nullable()->after('reason_changed');
        });
    }
};
