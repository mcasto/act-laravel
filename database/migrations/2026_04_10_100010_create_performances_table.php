<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('start_time')->default('15:00:00');
            $table->boolean('sold_out');
            $table->integer('sold_out_target')->default(50);
            $table->timestamps();
            $table->text('fixr_link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};
