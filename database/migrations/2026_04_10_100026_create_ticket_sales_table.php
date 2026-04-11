<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patron_id')->nullable()->constrained()->onDelete('set null');
            $table->uuid('transaction_id')->nullable();
            $table->date('transfer_date')->nullable();
            $table->foreignId('performance_id')->constrained()->onDelete('cascade');
            $table->dateTime('sold_at');
            $table->integer('quantity')->nullable();
            $table->integer('payment_method_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sales');
    }
};
