<?php
namespace Database\Seeders;

use App\Models\MailchimpMember;
use Illuminate\Database\Seeder;

class MailchimpMemberSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  MailchimpMember::factory(15)->create();
 }
}
