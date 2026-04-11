<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('angel_levels', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->integer('min_amount');
            $table->string('fixr_link');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('angel_levels');
    }
};
