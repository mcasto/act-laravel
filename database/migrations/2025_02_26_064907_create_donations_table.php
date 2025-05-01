<?php

use App\Models\DonationLevel;
use App\Models\Patron;
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
  Schema::create('donations', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Patron::class)->constrained()->onDelete('cascade');
   $table->integer('amount');
   $table->foreignIdFor(DonationLevel::class)->constrained()->onDelete('cascade');
   $table->date('date');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('donations');
 }
};
