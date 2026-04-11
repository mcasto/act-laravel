<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('last_name');
            $table->string('first_name');
            $table->text('email');
            $table->string('phone');
            $table->longText('questions')->nullable()->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_contacts');
    }
};
