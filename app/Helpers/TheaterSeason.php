<?php

namespace App\Helpers;

use App\Models\Performance;
use Carbon\Carbon;

class TheaterSeason
{
    /**
     * Returns the current season as a short string, e.g. '25-26'.
     * Theater seasons run September 1 through August 31.
     */
    public static function currentString(): string
    {
        $startYear = self::startYear();

        return substr($startYear, -2) . '-' . substr($startYear + 1, -2);
    }

    /**
     * Returns the current season as a full-year display string, e.g.
     * '2025-2026' — for on-page copy like the Angels page title, as
     * opposed to currentString()'s short form used for DB/season keys.
     */
    public static function currentDisplayString(): string
    {
        $startYear = self::startYear();

        return "{$startYear}-" . ($startYear + 1);
    }

    /**
     * Returns the start and end dates of the current theater season.
     *
     * @return array{start: string, end: string}
     */
    public static function currentDates(): array
    {
        $startYear = self::startYear();

        return [
            'start' => "{$startYear}-09-01",
            'end'   => ($startYear + 1) . '-08-31',
        ];
    }

    /**
     * Returns the start and end dates for a given season string (e.g. '25-26').
     *
     * @return array{start: string, end: string}
     */
    public static function datesForSeason(string $season): array
    {
        [$short] = explode('-', $season);
        $startYear = (int) ('20' . $short);

        return [
            'start' => "{$startYear}-09-01",
            'end'   => ($startYear + 1) . '-08-31',
        ];
    }

    private static function startYear(): int
    {
        $now = Carbon::now();

        // September or later = current year starts the season
        // August or earlier = previous year started the season
        return $now->month >= 9 ? $now->year : $now->year - 1;
    }

    /**
     * Which Sep1-Aug31 season bucket a given date falls into, e.g. '25-26'.
     * Same rule as currentString(), just for an arbitrary date instead of now.
     */
    public static function seasonForDate(string $date): string
    {
        $carbon = Carbon::parse($date);
        $year = $carbon->month >= 9 ? $carbon->year : $carbon->year - 1;

        return substr($year, -2) . '-' . substr($year + 1, -2);
    }

    /**
     * The season that should currently be on display on the public site
     * (homepage "up next"/upcoming carousel, the Season page) — driven by
     * actual performance data rather than a blind Sept 1 calendar flip, so
     * that:
     *   - adding next season's shows early doesn't bump them into view
     *     while this season's final show hasn't happened yet, and
     *   - the display *does* move on to next season as soon as this
     *     season's last performance has passed, even if that's before
     *     Sept 1 (e.g. an early-ending season).
     *
     * Deliberately separate from currentDates()/currentString(), which stay
     * pure calendar math — Angels and Flex-ticket redemption depend on that
     * not changing.
     *
     * @return array{start: string, end: string}
     */
    public static function activeDisplaySeasonDates(): array
    {
        $earliestUpcoming = Performance::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->first();

        if (! $earliestUpcoming) {
            return self::currentDates();
        }

        return self::datesForSeason(self::seasonForDate($earliestUpcoming->date));
    }
}
