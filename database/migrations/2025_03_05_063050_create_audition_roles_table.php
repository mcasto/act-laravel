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
  Schema::create('audition_roles', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Audition::class)->constrained()->onDelete('cascade');
   $table->text('sex');  // man/woman/any
   $table->text('name'); // character name
   $table->text('info'); // info about character
   $table->text('side'); // link to audition side for character (uploaded)
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('audition_roles');
 }
};
