<?php

use App\Models\Show;
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
  Schema::create('performances', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Show::class)->constrained()->onDelete('cascade');
   $table->date('date');
   $table->time('start_time');
   $table->integer('sold_out_target')->default(50);
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('performances');
 }
};
