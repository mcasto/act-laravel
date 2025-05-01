<?php

use App\Models\DonationLevel;
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
  Schema::create('donation_perks', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(DonationLevel::class)->constrained()->onDelete('cascade');
   $table->string('perk');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('donation_perks');
 }
};
