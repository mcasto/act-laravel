<?php

use App\Models\Patron;
use App\Models\PaymentMethod;
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
  Schema::create('patron_payment_ids', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Patron::class)->constrained()->onDelete('cascade');
   $table->foreignIdFor(PaymentMethod::class)->constrained()->onDelete('cascade');
   $table->string('external_id');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('patron_payment_ids');
 }
};
