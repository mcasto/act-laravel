<?php
namespace Database\Seeders;

use App\Models\PatronPaymentId;
use Illuminate\Database\Seeder;

class PatronPaymentIdSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  PatronPaymentId::factory(3)->create();
 }
}
