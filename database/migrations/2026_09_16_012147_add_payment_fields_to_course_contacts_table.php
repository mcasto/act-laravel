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
        Schema::table('course_contacts', function (Blueprint $table) {
            $table->foreignId('patron_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transfer_date')->nullable();
            $table->string('transaction_id')->nullable();
            $table->boolean('confirmed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patron_id');
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn(['transfer_date', 'transaction_id', 'confirmed']);
        });
    }
};
