<?php

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
        // One row per outgoing email, written by App\Services\MailLogger.
        // The row is created as the send starts and sent_at is stamped once
        // the mail driver accepts it — so a row with a null sent_at is an
        // email the app tried to send but the driver rejected/threw on.
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mailable')->nullable();
            $table->text('to')->nullable();
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->string('subject')->nullable();
            $table->string('mailersend_message_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_logs');
    }
};
