<?php
namespace Database\Factories;

use App\Models\FixrWebhookResponse;
use App\Models\Patron;
use App\Models\PaymentMethod;
use App\Models\Performance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $orderDate   = fake()->dateTimeThisMonth()->format('Y-m-d');
  $paymentDate = rand(1, 100) < 50 ? null : date("Y-m-d", strtotime("$orderDate + 1 day"));

  $performanceId = Performance::select('id')->inRandomOrder()->value('id');
  $patronId      = Patron::select('id')->inRandomOrder()->value('id');
  $methodId      = PaymentMethod::select('id')->inRandomOrder()->value('id');
  $fixrId        = FixrWebhookResponse::select('id')->inRandomOrder()->value('id');

  return [
   'performance_id'           => $performanceId,
   'patron_id'                => $patronId,
   'assigned_name'            => fake()->name(),
   'qty'                      => fake()->numberBetween(1, 5),
   'payment_method_id'        => $methodId,
   'order_date'               => $orderDate,
   'payment_date'             => $paymentDate,
   'fixr_webhook_response_id' => $fixrId,
  ];
 }
}
