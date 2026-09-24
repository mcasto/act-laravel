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
        Schema::table('comp_tickets', function (Blueprint $table) {
            // The real TicketSale (+ its one Ticket row) this comp spawns
            // on redemption — see CompTixController::redeemComp(). Kept
            // separate from comp_tickets itself, which stays the source of
            // truth for the issue/redeem workflow (uid, sent_at, etc.).
            $table->foreignId('ticket_sale_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comp_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_sale_id');
        });
    }
};
