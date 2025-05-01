<?php

use App\Models\Audition;
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
  Schema::create('audition_sessions', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Audition::class)->constrained()->onDelete('cascade');
   $table->dateTime('session');
   $table->text('location_name')->default('Azuay Community Theater');
   $table->text('location_address')->default('14-46 Antonio Vega Muñoz (between Estevez de Toral and Coronel Talbot)');
   $table->text('location_map_link')->default('https://maps.app.goo.gl/dHxNiTyCmL9A3LRZ8');
   $table->longText('notes');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('audition_sessions');
 }
};
