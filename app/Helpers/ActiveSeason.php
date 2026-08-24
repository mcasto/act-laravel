<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * A manually-set override for which season NEW Angel donation records get
 * tagged with. Deliberately separate from TheaterSeason's calendar-based
 * Oct 1 - Aug 31 calculation: Angel promotion for a season starts before the
 * previous season's Aug 31 cutoff, but shows and Flex-ticket redemption
 * still need TheaterSeason's real dates, so this must never replace that.
 */
class ActiveSeason
{
    private const FILE = 'active-angel-season.txt';

    /** Returns the stored override, or the calculated current season if none has been set. */
    public static function get(): string
    {
        if (Storage::disk('local')->exists(self::FILE)) {
            $value = trim(Storage::disk('local')->get(self::FILE));

            if ($value !== '') {
                return $value;
            }
        }

        return TheaterSeason::currentString();
    }

    public static function set(string $season): void
    {
        Storage::disk('local')->put(self::FILE, $season);
    }
}
