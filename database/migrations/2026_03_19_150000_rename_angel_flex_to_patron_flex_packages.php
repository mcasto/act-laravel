<?php

use App\Models\Patron;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('angel_flex', 'patron_flex_packages');

        Schema::table('patron_flex_packages', function (Blueprint $table) {
            $table->foreignIdFor(Patron::class)->after('id')->constrained()->onDelete('cascade');
            $table->string('season')->after('patron_id');
            $table->integer('tickets_purchased')->default(6)->after('season');
            $table->timestamp('purchased_at')->nullable()->after('tickets_purchased');
        });
    }

    public function down(): void
    {
        Schema::table('patron_flex_packages', function (Blueprint $table) {
            $table->dropForeign(['patron_id']);
            $table->dropColumn(['patron_id', 'season', 'tickets_purchased', 'purchased_at']);
        });

        Schema::rename('patron_flex_packages', 'angel_flex');
    }
};
