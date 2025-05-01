<?php
namespace Database\Seeders;

use App\Models\MailchimpList;
use Illuminate\Database\Seeder;

class MailchimpListSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  MailchimpList::factory(3)->create();
 }
}
