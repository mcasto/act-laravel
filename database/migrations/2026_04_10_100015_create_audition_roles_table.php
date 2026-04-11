<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audition_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audition_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->longText('notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audition_roles');
    }
};
