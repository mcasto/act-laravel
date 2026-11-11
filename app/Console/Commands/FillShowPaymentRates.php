<?php

namespace App\Console\Commands;

use App\Models\ShowPaymentRate;
use Illuminate\Console\Command;

/**
 * Backfill: gives every show a revenue multiplier for each per-show
 * payment method it doesn't have one for yet, at the method's current
 * global multiplier. Only adds — a show's existing rates (including
 * overrides) are never changed or removed. Turning on a method's Per Show
 * toggle does the same thing for that method automatically.
 */
class FillShowPaymentRates extends Command
{
    protected $signature = 'shows:fill-payment-rates';

    protected $description = 'Add missing per-show revenue multipliers to every show (never changes existing ones)';

    public function handle(): int
    {
        $added = ShowPaymentRate::addMissing();
        $this->info("Added {$added} show revenue multiplier" . ($added === 1 ? '' : 's') . '.');

        return self::SUCCESS;
    }
}
