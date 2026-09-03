<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->text('guest_list')->nullable()->after('reason_changed');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropColumn('guest_list');
        });
    }
};
