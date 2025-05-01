<?php
namespace Database\Seeders;

use App\Models\DonationPerk;
use Illuminate\Database\Seeder;

class DonationPerkSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  DonationPerk::factory(10)->create();
 }
}
