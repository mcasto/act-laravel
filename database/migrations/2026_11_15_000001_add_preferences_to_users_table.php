<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user admin UI preferences (e.g. ticket_sales_view: the default view
     * on the Ticket Sales page). Keys are whitelisted in
     * AuthController::updatePreferences().
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferences')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferences');
        });
    }
};
