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
        // A public "Use Your Flex Package" request waiting on the patron to
        // click the emailed confirm link — proves whoever submitted the form
        // actually controls that patron's email before their flex tickets
        // get spent. No TicketSale exists until it's confirmed.
        Schema::create('pending_flex_redemptions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('patron_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->text('special_request')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('ticket_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_flex_redemptions');
    }
};
