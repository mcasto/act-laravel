<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * A patron-facing purchase option (PayPal, bank transfer, Flex, "message
 * us") shown alongside the always-first Fixr card option. Configured on
 * the admin Payment Methods page. Separate from PaymentMethod, which is
 * the box office's accounting list of how a sale was paid.
 */
class StandardButton extends Model
{
    use SoftDeletes;

    private const PATRON_CACHE_KEY = 'standard-buttons-patron';

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget(self::PATRON_CACHE_KEY));
        static::deleted(fn() => Cache::forget(self::PATRON_CACHE_KEY));
    }

    protected $fillable = [
        'label',
        'key',
        'sort_order',
        'display_to_patrons',
    ];

    protected $casts = [
        'display_to_patrons' => 'boolean',
    ];

    /**
     * The buttons patrons should see, in display order — the single source
     * for every patron-facing purchase list. Callers map over the result to
     * attach popupText, so each gets fresh model instances from the cache.
     */
    public static function forPatrons(): Collection
    {
        return Cache::remember(
            self::PATRON_CACHE_KEY,
            3600,
            fn() => self::where('display_to_patrons', true)->orderBy('sort_order')->get()
        );
    }
}
