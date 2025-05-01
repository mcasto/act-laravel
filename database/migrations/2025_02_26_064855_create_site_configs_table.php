<?php

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 use SoftDeletes;

 /**
  * Run the migrations.
  */
 public function up(): void
 {
  Schema::create('site_configs', function (Blueprint $table) {
   $table->id();
   $table->integer('ticket_price');
   $table->string('ticket_email');
   $table->string('contact_email');
   $table->string('dev_email');
   $table->integer('sold_out_target');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('site_configs');
 }
};
