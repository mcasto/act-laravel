<?php

use App\Models\FixrWebhookResponse;
use App\Models\Patron;
use App\Models\PaymentMethod;
use App\Models\Performance;
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
  Schema::create('tickets', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(Performance::class)->constrained()->onDelete('cascade');
   $table->foreignIdFor(Patron::class)->constrained()->onDelete('cascade');
   $table->string('assigned_name');
   $table->integer('qty');
   $table->foreignIdFor(PaymentMethod::class)->constrained()->onDelete('cascade');
   $table->date('order_date');
   $table->date('payment_date')->nullable();
   $table->foreignIdFor(FixrWebhookResponse::class)->constrained()->onDelete('cascade');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('tickets');
 }
};
