<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reservation reminders — log-only until RESERVATION_REMINDERS_SEND=true
// (see SendReservationReminders). Day-before batch at 10:00 local for
// tomorrow's performances; same-day batch checked every five minutes and
// fired once per performance, 4 hours before curtain, for anyone not
// already reminded (late purchases / reschedules).
Schedule::command('reminders:send')
    ->dailyAt('10:00')
    ->timezone(config('app.local_timezone'));

Schedule::command('reminders:send --same-day')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('storage:cleanup-orphans')
    ->dailyAt('03:00')
    ->timezone(config('app.local_timezone'));

// sync:flex-sheet retired — The Nightingales was the season finale and is
// fully sold out, so no further flex usage is possible for 25-26. It was
// hardcoded to that season's shows anyway and will need a rewrite before
// it'd be relevant for 26-27.
