<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MailchimpList>
 */
class MailchimpListFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  return [
   'name'         => fake()->word(),
   'mailchimp_id' => fake()->uuid(),
  ];
 }
}
