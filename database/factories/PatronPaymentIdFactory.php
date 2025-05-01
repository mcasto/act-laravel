<?php
namespace Database\Factories;

use App\Models\Patron;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patron_payment_id>
 */
class PatronPaymentIdFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $patronId = Patron::select('id')->inRandomOrder()->value('id');
  $methodId = PaymentMethod::select('id')->inRandomOrder()->value('id');

  return [
   'patron_id'         => $patronId,
   'payment_method_id' => $methodId,
   'external_id'       => fake()->uuid(),
  ];
 }
}
