<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comp_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->onDelete('cascade');
            $table->uuid('uid');
            $table->string('name');
            $table->string('email');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('performance_id')->nullable()->constrained()->onDelete('set null');
            $table->string('pickup_name')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_tickets');
    }
};
