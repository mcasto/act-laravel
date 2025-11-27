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
        Schema::create('ticket_sales', function (Blueprint $table) {
            $table->id();
            $table->string('show');
            $table->dateTime('performance');
            $table->dateTime('sold_at');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('mobile_number');
            $table->string('contact_preferences_user_response');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_sales');
    }
};
