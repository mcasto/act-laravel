<?php

use App\Models\AngelLevel;
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
        Schema::create('angels', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AngelLevel::class)->constrained()->onDelete('cascade');
            $table->string('name');
            $table->boolean('founding_angel');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('angels');
    }
};
