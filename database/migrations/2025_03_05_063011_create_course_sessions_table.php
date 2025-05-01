<?php

use App\Models\Course;
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
  Schema::create('course_sessions', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Course::class)->constrained()->onDelete('cascade');
   $table->dateTime('date');
   $table->time('start');
   $table->time('end');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('course_sessions');
 }
};
