<?php
namespace Database\Factories;

use App\Models\Show;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Performance>
 */
class PerformanceFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $id = Show::select('id')->inRandomOrder()->value('id');

  return [
   'show_id'         => $id,
   'date'            => fake()->dateTimeThisMonth()->format('Y-m-d'),
   'start_time'      => '15:00:00',
   'sold_out_target' => rand(35, 50),

  ];
 }
}
