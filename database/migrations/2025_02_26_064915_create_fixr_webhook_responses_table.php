<?php

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
  Schema::create('fixr_webhook_responses', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Patron::class)->nullable()->constrained()->onDelete('cascade');
   $table->string('event');
   $table->json('payload');
   $table->string('message_id');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('fixr_webhook_responses');
 }
};
