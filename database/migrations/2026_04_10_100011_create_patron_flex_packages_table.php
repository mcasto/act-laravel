<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patron_flex_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patron_id')->constrained()->onDelete('cascade');
            $table->string('season');
            $table->integer('tickets_purchased')->default(6);
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patron_flex_packages');
    }
};
