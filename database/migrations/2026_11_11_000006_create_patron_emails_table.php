<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every email address a patron uses — the main one (patrons.email,
     * where email goes) plus any others. Each address belongs to one
     * patron only, so a purchase under any of them finds the same patron
     * (Patron::findByEmail()). Stored lowercased and trimmed.
     *
     * Seeded with each patron's current email, skipping blanks and values
     * that aren't email addresses (e.g. a name typed into the field).
     */
    public function up(): void
    {
        Schema::create('patron_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patron_id')->constrained()->cascadeOnDelete();
            $table->string('email')->unique();
            $table->timestamps();
        });

        $seen = [];
        $now = now();

        DB::table('patrons')->whereNull('deleted_at')->orderBy('id')->get(['id', 'email'])
            ->each(function ($patron) use (&$seen, $now) {
                // Same cleanup as Patron::normalizeEmail() (inlined so this
                // migration doesn't depend on the model changing later).
                $email = strtolower(trim((string) $patron->email));
                $email = trim(preg_replace('/^mailto:/', '', $email), " \t\n\r\0\x0B<>\"'");

                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                    return;
                }

                $seen[$email] = true;
                DB::table('patron_emails')->insert([
                    'patron_id'  => $patron->id,
                    'email'      => $email,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('patron_emails');
    }
};
