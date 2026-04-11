<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('angels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('angel_level_id')->constrained()->onDelete('cascade');
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('recognition_name');
            $table->decimal('donation_amount', 8, 2)->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->text('benefit')->nullable();
            $table->string('season')->nullable();
            $table->boolean('founding_angel');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('angels');
    }
};
