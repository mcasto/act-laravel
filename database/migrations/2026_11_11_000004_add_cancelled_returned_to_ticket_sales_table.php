<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Of a Flex or comp sale's cancelled tickets, how many were returned to
     * the patron (0..cancelled) — the admin is asked when cancelling.
     * Returned Flex tickets go back to the patron's Flex balance; a
     * returned comp goes back to being an unused comp. Cancelled tickets
     * that weren't returned stay counted as used.
     */
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancelled_returned')->default(0)->after('cancelled');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('cancelled_returned');
        });
    }
};
