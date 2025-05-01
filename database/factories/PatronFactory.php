<?php
namespace Database\Factories;

use App\Models\MailchimpMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patron>
 */
class PatronFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
// get a random mailchimp member
  $id = MailchimpMember::select('id')->inRandomOrder()->value('id');

  return [
   'name'                => fake()->name(),
   'mailchimp_member_id' => $id,
   'email'               => fake()->email(),
   'phone'               => fake()->e164PhoneNumber(),
  ];
 }
}
