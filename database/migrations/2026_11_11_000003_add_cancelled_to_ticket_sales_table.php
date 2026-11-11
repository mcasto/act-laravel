<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many of the sale's tickets were cancelled (0..quantity). A
     * cancellation keeps the record — deleting a sale is only for a
     * mistaken or test entry. Seats actually held = quantity - cancelled
     * (TicketSale::active_quantity); a fully cancelled sale holds none.
     */
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancelled')->default(0)->after('no_show');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('cancelled');
        });
    }
};
