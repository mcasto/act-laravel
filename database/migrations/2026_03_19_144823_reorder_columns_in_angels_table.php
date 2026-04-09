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
        Schema::table('angels', function (Blueprint $table) {
            $table->unsignedBigInteger('angel_level_id')->after('id')->change();
            $table->string('last_name')->nullable()->after('angel_level_id')->change();
            $table->string('first_name')->nullable()->after('last_name')->change();
            $table->string('recognition_name')->after('first_name')->change();
            $table->decimal('donation_amount', 8, 2)->nullable()->after('recognition_name')->change();
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('donation_amount')->change();
            $table->text('benefit')->nullable()->after('payment_method_id')->change();
            $table->integer('year_start')->nullable()->after('benefit')->change();
            $table->integer('year_end')->nullable()->after('year_start')->change();
            $table->boolean('founding_angel')->after('year_end')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('angels', function (Blueprint $table) {
            $table->unsignedBigInteger('angel_level_id')->after('id')->change();
            $table->string('recognition_name')->after('angel_level_id')->change();
            $table->string('last_name')->nullable()->after('recognition_name')->change();
            $table->string('first_name')->nullable()->after('last_name')->change();
            $table->text('benefit')->nullable()->after('first_name')->change();
            $table->decimal('donation_amount', 8, 2)->nullable()->after('benefit')->change();
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('donation_amount')->change();
            $table->integer('year_start')->nullable()->after('payment_method_id')->change();
            $table->integer('year_end')->nullable()->after('year_start')->change();
            $table->boolean('founding_angel')->after('year_end')->change();
        });
    }
};
