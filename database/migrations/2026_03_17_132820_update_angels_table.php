<?php

use App\Models\PaymentMethod;
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
            $table->renameColumn('name', 'recognition_name');
            $table->string('last_name');
            $table->string('first_name');
            $table->text('benefit');
            $table->decimal('donation_amount', 8, 2);
            $table->foreignIdFor(PaymentMethod::class)
                ->constrained()
                ->onDelete('cascade');
            $table->integer('year_start');
            $table->integer('year_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('angels', function (Blueprint $table) {
            $table->renameColumn('recognition_name', 'name');
        });
    }
};
