<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether the purchaser's Angel concession benefits print on the door
     * sheet for this sale. On by default; the box office turns it off on
     * the ticket sale form (e.g. an Angel buying tickets for someone else).
     */
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->boolean('allow_angel_benefits')->default(true)->after('front_row');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('allow_angel_benefits');
        });
    }
};
