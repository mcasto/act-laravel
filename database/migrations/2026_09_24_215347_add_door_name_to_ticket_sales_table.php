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
        Schema::table('ticket_sales', function (Blueprint $table) {
            // Overrides what prints at the door (see AdminTicketSalesPrint.vue)
            // without touching patron_id, so flex-balance/angel-benefit
            // lookups and everything else keyed off the real patron stay
            // correct even when the printed name is overridden. Defaults to
            // the purchaser's name on the New Ticket Sale form; nullable
            // here since existing rows are backfilled separately (see
            // BackfillDoorNames console command).
            $table->string('door_last')->nullable();
            $table->string('door_first')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn(['door_last', 'door_first']);
        });
    }
};
