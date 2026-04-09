<?php
namespace Database\Seeders;

use App\Models\TicketSale;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  TicketSale::factory(3)->create();
 }
}
