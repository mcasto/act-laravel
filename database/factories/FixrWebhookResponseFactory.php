<?php
namespace Database\Factories;

use App\Models\Patron;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FixrWebhookResponse>
 */
class FixrWebhookResponseFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $id = Patron::select('id')->inRandomOrder()->value('id');

  return [
   'patron_id'  => $id,
   'event'      => fake()->bs(),
   'payload'    => json_encode([
//
   ]),
   'message_id' => fake()->uuid(),
  ];
 }
}
