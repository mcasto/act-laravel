<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-show revenue multipliers, so a later change to a payment method's
     * global multiplier (e.g. a new card fee) doesn't rewrite past shows'
     * revenue.
     *
     * payment_methods.per_show — methods whose multiplier is kept per show.
     * Turning it on adds a row (at the current global multiplier) to every
     * show that doesn't have one; a new show gets a row for each per-show
     * method when it's created. Rows are only ever added that way, never
     * changed or removed — a show's rate changes only when an admin edits
     * it on that show. See ShowPaymentRate::addMissing().
     *
     * A show with no row for a method uses the method's global multiplier.
     */
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('per_show')->default(false)->after('revenue_multiplier');
        });

        Schema::create('show_payment_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->decimal('revenue_multiplier', 8, 4)->nullable();
            $table->timestamps();
            $table->unique(['show_id', 'payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('show_payment_rates');
        Schema::table('payment_methods', fn (Blueprint $table) => $table->dropColumn('per_show'));
    }
};
