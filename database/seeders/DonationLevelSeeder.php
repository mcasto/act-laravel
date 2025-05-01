<?php
namespace Database\Seeders;

use App\Models\DonationLevel;
use Illuminate\Database\Seeder;

class DonationLevelSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  DonationLevel::create([
   'label'      => 'ACT Angel',
   'value'      => 'act-angel',
   'amount_min' => 100,
   'amount_max' => 249,
  ]);

  DonationLevel::create([
   'label'      => 'ACT Archangel',
   'value'      => 'act-archangel',
   'amount_min' => 250,
   'amount_max' => 499,
  ]);

  DonationLevel::create([
   'label'      => 'ACT Guardian Angel',
   'value'      => 'act-guardian-angel',
   'amount_min' => 500,
   'amount_max' => 999,
  ]);

  DonationLevel::create([
   'label'      => 'ACT Seraphim and Producers',
   'value'      => 'act-seraphim',
   'amount_min' => 1000,
   'amount_max' => 99999999,
  ]);

 }
}
