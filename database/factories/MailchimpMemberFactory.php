<?php
namespace Database\Factories;

use App\Models\MailchimpList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MailchimpMember>
 */
class MailchimpMemberFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
// get a random mailchimp_list_id
  $id = MailchimpList::select('id')->inRandomOrder()->value('id');

  $rec = [
   'mailchimp_list_id' => $id,
   'email'             => fake()->email(),
   'status'            => 'subscribed',
  ];

  return $rec;
 }
}
