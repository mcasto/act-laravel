<?php

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use Illuminate\Console\Command;

class AssignPaymentMethodColors extends Command
{
    protected $signature = 'payment-methods:assign-colors';

    protected $description = 'Assign a distinct color to each payment method';

    public function handle(): void
    {
        $colors = [
            '#e53935', // red
            '#1e88e5', // blue
            '#43a047', // green
            '#fb8c00', // orange
            '#8e24aa', // purple
            '#00acc1', // cyan
            '#f4511e', // deep orange
            '#3949ab', // indigo
            '#00897b', // teal
            '#c0ca33', // lime
        ];

        $methods = PaymentMethod::all();

        if ($methods->count() > count($colors)) {
            $this->error('More payment methods than available colors.');
            return;
        }

        shuffle($colors);

        foreach ($methods as $index => $method) {
            $method->color = $colors[$index];
            $method->save();
            $this->line("  {$method->label}: {$colors[$index]}");
        }

        $this->info('Colors assigned.');
    }
}
