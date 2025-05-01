<?php
namespace Database\Seeders;

use App\Models\Performance;
use Illuminate\Database\Seeder;

class PerformanceSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  Performance::factory(18)->create();
 }
}
