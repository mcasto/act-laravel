<?php

use App\Models\Patron;
use App\Models\PatronFlexPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->foreignIdFor(Patron::class)->after('id')->nullable()->constrained()->onDelete('set null');
            $table->foreignIdFor(PatronFlexPackage::class)->nullable()->after('patron_id')->constrained('patron_flex_packages')->onDelete('set null');
            $table->uuid('transaction_id')->nullable()->after('patron_flex_package_id');

            $table->dropColumn([
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'contact_preferences_user_response',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_sales', function (Blueprint $table) {
            $table->dropForeign(['patron_id']);
            $table->dropForeign(['patron_flex_package_id']);
            $table->dropColumn(['patron_id', 'patron_flex_package_id', 'transaction_id']);

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('contact_preferences_user_response')->nullable();
        });
    }
};
