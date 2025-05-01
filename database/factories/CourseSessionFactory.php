<?php
namespace Database\Factories;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseSession>
 */
class CourseSessionFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $course = Course::first()->toArray();

  $numDays = rand(40, 70);

  return [
   'course_id' => $course['id'],
   'date'      => Carbon::now()->addDay($numDays)->toDateString(),
   'start'     => '09:30:00',
   'end'       => '11:30:00',
  ];
 }
}
