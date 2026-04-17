<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'angel_levels',
        'angels',
        'audition_roles',
        'audition_sessions',
        'auditions',
        'comp_tickets',
        'course_contacts',
        'course_sessions',
        'courses',
        'donation_levels',
        'donations',
        'fixr_webhook_responses',
        'gallery_images',
        'patron_flex_packages',
        'patrons',
        'payment_methods',
        'performances',
        'permission_levels',
        'site_configs',
        'skills',
        'standard_buttons',
        'ticket_sales',
        'user_permissions',
        'users',
        'volunteer_skills',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
