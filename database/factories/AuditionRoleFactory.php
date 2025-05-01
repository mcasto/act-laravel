<?php
namespace Database\Factories;

use App\Models\Audition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditionRole>
 */
class AuditionRoleFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $audition = Audition::first()->toArray();

  return [
   'audition_id' => $audition['id'],
   'sex'         => fake()->randomElement(['man', 'woman', 'any']),
   'name'        => fake()->name(),
   'info'        => fake()->paragraphs(3, true),
   'side'        => parse_url(fake()->url(), PHP_URL_PATH),
  ];
 }
}
