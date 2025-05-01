<?php
namespace Database\Seeders;

use App\Models\FixrWebhookResponse;
use Illuminate\Database\Seeder;

class FixrWebhookResponseSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  FixrWebhookResponse::factory(3)->create();
 }
}
