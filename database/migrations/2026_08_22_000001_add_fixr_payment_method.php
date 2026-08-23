<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_methods')->insert([
            'label' => 'Fixr',
            'value' => 'fixr',
            'user_option' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('value', 'fixr')->delete();
    }
};
