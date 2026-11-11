<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which emails are switched off on the admin Email Templates page. One
     * row per email that's been switched off or back on; an email with no
     * row is on. key = the email's template (view) name — see
     * App\Support\EmailTemplates. Template edits themselves are files in
     * storage/app/email-templates, not rows here.
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
