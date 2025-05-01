<?php
namespace Database\Seeders;

use App\Models\PermissionLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionLevelSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  $levels = [
   'volunteers',
   'tickets',
   'shows',
   'patrons',
   'donations',
   'users',
   'contacts',
   'site-config',
   'payments',
  ];

  foreach ($levels as $level) {
   $rec = ['label' => Str::headline($level), 'value' => $level];
   PermissionLevel::create($rec);
  }
 }
}
