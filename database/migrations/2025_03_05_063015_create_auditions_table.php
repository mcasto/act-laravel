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
  Schema::create('auditions', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Show::class)->constrained()->onDelete('cascade');
   $table->date('display_date');     // start displaying audition info
   $table->text('script');           // script PDF link (PDF uploaded to storage/scripts)
   $table->string('director_email'); // needed for sending audtiion contact
   $table->date('first_read');       // first read through date
   $table->date('rehearsals_start'); // regular rehearsals start date
   $table->longText('about');        // about the play
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('auditions');
 }
};
