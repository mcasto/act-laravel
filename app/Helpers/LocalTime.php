<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * The theater's local clock (Cuenca, Ecuador — config('app.local_timezone')).
 *
 * The app itself runs and stores timestamps in UTC (config('app.timezone')),
 * but every day-boundary decision — "is it today yet", "has this date
 * passed", "is this window open", season flips — must switch over at local
 * midnight, not 7 PM local (UTC midnight). Use this instead of now()/today()
 * whenever comparing against a DATE column or a local calendar date.
 *
 * Ecuador has no DST, so local midnight is always 05:00 UTC.
 *
 * Compare date columns against todayString() (Y-m-d strings), never against a
 * Carbon instant — a date-cast column is midnight *UTC*, so comparing it with
 * a local-midnight instant is off by five hours.
 */
class LocalTime
{
    public static function zone(): string
    {
        return config('app.local_timezone');
    }

    /** Current local time (Carbon in the local zone). */
    public static function now(): Carbon
    {
        return Carbon::now(self::zone());
    }

    /** Today's local date, e.g. '2026-11-08'. */
    public static function todayString(): string
    {
        return self::now()->toDateString();
    }

    /** A local calendar date (and optional time) as a Carbon in the local zone. */
    public static function parse(string $dateTime): Carbon
    {
        return Carbon::parse($dateTime, self::zone());
    }
}
