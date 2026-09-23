<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verification mode — reminders:send currently logs to
// storage/app/private/reservation-reminder-log.csv instead of actually
// emailing anyone (see SendReservationReminders::logWouldSend()), after an
// earlier erroneous send caused problems. Re-enabled here so that log can
// build up real data to verify against before flipping it back to
// Mail::send().
Schedule::command('reminders:send')
    ->dailyAt('10:00')
    ->timezone('America/Guayaquil');

Schedule::command('storage:cleanup-orphans')
    ->dailyAt('03:00')
    ->timezone('America/Guayaquil');

// sync:flex-sheet retired — The Nightingales was the season finale and is
// fully sold out, so no further flex usage is possible for 25-26. It was
// hardcoded to that season's shows anyway and will need a rewrite before
// it'd be relevant for 26-27.
