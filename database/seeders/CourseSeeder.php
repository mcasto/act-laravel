<?php
namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  Course::factory(1)->create();
  CourseSession::factory(8)->create();
 }
}
