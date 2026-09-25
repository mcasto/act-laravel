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
            // Defaults to the patron's own Patron::comments on the New
            // Ticket Sale form, but editable/overridable per sale from
            // there — see AdminTicketSaleForm.vue.
            $table->text('comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
    }
};
