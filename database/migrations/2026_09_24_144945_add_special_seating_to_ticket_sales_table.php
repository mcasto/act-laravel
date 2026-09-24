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
            // Reserved seats for this sale's whole party (e.g. an Angel
            // donor level that grants reserved seating for their group) —
            // distinct from Patron::front_row, which is a standing
            // accessibility accommodation for one patron, not tied to a
            // sale. A party can be much larger than a front-row disability
            // accommodation, hence the higher cap (enforced in
            // TicketSaleController, not here).
            $table->unsignedTinyInteger('special_seating')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('special_seating');
        });
    }
};
