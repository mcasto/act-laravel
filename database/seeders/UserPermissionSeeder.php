<?php

namespace Database\Seeders;

use App\Models\UserPermission;
use Illuminate\Database\Seeder;

class UserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($pid = 1; $pid < 10; $pid++) {
            UserPermission::create([
                'user_id' => 1,
                'permission_level_id' => $pid,
                'access' => 'full'
            ]);
        }
    }
}
