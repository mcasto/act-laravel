<?php

namespace Database\Factories;

use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\Performance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketSale>
 */
class TicketSaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $performance   = Performance::with('show')->inRandomOrder()->first();
        $patronId      = Patron::select('id')->inRandomOrder()->value('id');
        $flexPackageId = PatronFlexPackage::select('id')->inRandomOrder()->value('id');

        return [
            'patron_id'              => $patronId,
            'patron_flex_package_id' => rand(0, 1) ? $flexPackageId : null,
            'transaction_id'         => fake()->uuid(),
            'show'                   => $performance?->show?->title ?? fake()->sentence(3),
            'performance'            => $performance?->date . ' ' . $performance?->start_time ?? now()->format('Y-m-d H:i:s'),
            'performance_id'         => $performance?->id,
            'sold_at'                => fake()->dateTimeThisMonth()->format('Y-m-d H:i:s'),
            'quantity'               => fake()->numberBetween(1, 4),
            'fixr_webhook_response_id' => null,
        ];
    }
}
