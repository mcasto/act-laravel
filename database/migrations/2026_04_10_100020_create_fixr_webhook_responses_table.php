<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixr_webhook_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patron_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event');
            $table->longText('payload');
            $table->string('message_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixr_webhook_responses');
    }
};
