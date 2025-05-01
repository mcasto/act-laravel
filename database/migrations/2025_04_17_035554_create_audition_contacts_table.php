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
  Schema::create('audition_contacts', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Audition::class)->constrained()->onDelete('cascade');
   $table->string('name');
   $table->string('role');
   $table->string('email');
   $table->string('phone');
   $table->string('body')->default('');
   $table->longText('sendgrid_response')->default('{}');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('audition_contacts');
 }
};
