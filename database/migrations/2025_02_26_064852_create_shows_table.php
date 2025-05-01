<?php

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
  Schema::create('shows', function (Blueprint $table) {
   $table->id();
   $table->string('name');
   $table->string('writer');
   $table->string('tagline');
   $table->string('director');
   $table->longText('info')->nullable();
   $table->string('poster');
   $table->date('ticket_sales_start');
   $table->string('slug');
   $table->softDeletes();
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('shows');
 }
};
