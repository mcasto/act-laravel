<?php
namespace Database\Seeders;

use App\Models\SiteConfig;
use Illuminate\Database\Seeder;

class SiteConfigSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  SiteConfig::create([
   'ticket_price'    => 15,
   'ticket_email'    => 'actseats@gmail.com',
   'contact_email'   => 'actseats@gmail.com',
   'dev_email'       => 'castoware@gmail.com',
   'sold_out_target' => 50,
  ]);
 }
}
