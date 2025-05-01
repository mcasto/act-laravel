<?php
namespace Database\Factories;

use App\Models\DonationLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DonationPerk>
 */
class DonationPerkFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $id = DonationLevel::select('id')->inRandomOrder()->value('id');

  return [
   'donation_level_id' => $id,
   'perk'              => fake()->sentence(),
  ];
 }
}
