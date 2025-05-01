<?php
namespace Database\Seeders;

use App\Models\VolunteerSkill;
use Illuminate\Database\Seeder;

class VolunteerSkillSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  VolunteerSkill::factory(30)->create();
 }
}
