<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentMethod::create([
            'label'       => 'PayPal',
            'value'       => 'paypal',
            'user_option' => true,
        ]);

        PaymentMethod::create([
            'label'       => 'Ecuadorian Bank Transfer',
            'value'       => 'transfer',
            'user_option' => true,
        ]);

        PaymentMethod::create([
            'label'       => 'Credit Card',
            'value'       => 'credit_card',
            'user_option' => true,
        ]);

        PaymentMethod::create([
            'label'       => 'Comp Ticket',
            'value'       => 'comp',
            'user_option' => false,
        ]);

        PaymentMethod::create([
            'label'       => 'Flex',
            'value'       => 'flex',
            'user_option' => true,
        ]);

        PaymentMethod::create([
            'label'       => 'Cash',
            'value'       => 'cash',
            'user_option' => false,
        ]);

        PaymentMethod::create([
            'label'       => 'Raffle',
            'value'       => 'raffle',
            'user_option' => false,
        ]);
    }
}
