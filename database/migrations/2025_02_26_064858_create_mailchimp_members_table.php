<?php

use App\Models\MailchimpList;
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
  Schema::create('mailchimp_members', function (Blueprint $table) {
   $table->id();
   $table->foreignIdFor(MailchimpList::class)->constrained()->onDelete('cascade');
   $table->string('email')->unique();
   $table->string('status')->default('subscribed');
   $table->timestamps();
  });
 }

 /**
  * Reverse the migrations.
  */
 public function down(): void
 {
  Schema::dropIfExists('mailchimp_members');
 }
};
