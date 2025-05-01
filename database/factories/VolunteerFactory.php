<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Volunteer>
 */
class VolunteerFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $rec = [
   'name'       => fake()->name(),
   'email'      => fake()->email(),
   'phone'      => fake()->e164PhoneNumber(),
   'experience' => fake()->paragraphs(3, true),
   'active'     => fake()->boolean(),
  ];

  return $rec;
 }
}
