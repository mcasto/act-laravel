<?php
namespace Database\Factories;

use App\Models\DonationLevel;
use App\Models\Patron;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $patronId        = Patron::select('id')->inRandomOrder()->value('id');
  $donationLevelId = DonationLevel::select('id')->inRandomOrder()->value('id');

  $amounts = [100, 250, 500, 1000];
  shuffle($amounts);

  return [
   'patron_id'         => $patronId,
   'amount'            => array_shift($amounts),
   'donation_level_id' => $donationLevelId,
   'date'              => fake()->date(),
  ];
 }
}
