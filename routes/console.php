<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// DISABLED — reservation reminder emails temporarily turned off. Re-enable by
// uncommenting once ready to resume.
// Schedule::command('reminders:send')
//     ->dailyAt('10:00')
//     ->timezone('America/Guayaquil');

Schedule::command('storage:cleanup-orphans')
    ->dailyAt('03:00')
    ->timezone('America/Guayaquil');

// sync:flex-sheet retired — The Nightingales was the season finale and is
// fully sold out, so no further flex usage is possible for 25-26. It was
// hardcoded to that season's shows anyway and will need a rewrite before
// it'd be relevant for 26-27.
