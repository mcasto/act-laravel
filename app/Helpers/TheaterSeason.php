<?php

namespace App\Helpers;

use Carbon\Carbon;

class TheaterSeason
{
    /**
     * Returns the current season as a short string, e.g. '25-26'.
     * Theater seasons run October 1 through August 31.
     */
    public static function currentString(): string
    {
        $startYear = self::startYear();

        return substr($startYear, -2) . '-' . substr($startYear + 1, -2);
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
            'start' => "{$startYear}-10-01",
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
            'start' => "{$startYear}-10-01",
            'end'   => ($startYear + 1) . '-08-31',
        ];
    }

    private static function startYear(): int
    {
        $now = Carbon::now();

        // October or later = current year starts the season
        // September or earlier = previous year started the season
        return $now->month >= 10 ? $now->year : $now->year - 1;
    }
}
