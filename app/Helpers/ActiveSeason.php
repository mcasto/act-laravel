<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * A manually-set override for which season NEW Angel donation records and
 * NEW Flex package purchases get tagged with. Deliberately separate from
 * TheaterSeason's calendar-based Oct 1 - Aug 31 calculation: Angel/Flex
 * promotion for a season starts before the previous season's Aug 31 cutoff,
 * but shows and Flex-ticket redemption still need TheaterSeason's real
 * dates, so this must never replace that. Kept as its own file (not part of
 * the flex-purchase-config.json content) since it's a cross-cutting site
 * setting, not page copy.
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
