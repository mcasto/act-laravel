<?php

use App\Models\MailchimpMember;
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
  Schema::create('patrons', function (Blueprint $table) {
   $table->id();
   $table->string('name');
   $table->foreignIdFor(MailchimpMember::class)->constrained()->onDelete('cascade');
   $table->string('email');
   $table->string('phone');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('patrons');
 }
};
