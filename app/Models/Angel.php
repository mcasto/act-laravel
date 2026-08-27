<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Angel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'angel_level_id',
        'patron_id',
        'recognition_name',
        'last_name',
        'first_name',
        'benefit',
        'donation_amount',
        'payment_method_id',
        'founding_angel',
        'season',
    ];

    protected $casts = [
        'founding_angel' => 'integer',
    ];

    public function angelLevel(): BelongsTo
    {
        return $this->belongsTo(AngelLevel::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->patron?->email,
        );
    }

    /**
     * Founding-angel status is a permanent fact about the donor (were they
     * giving when the Angel program started?), not something that resets
     * per donation or per season. This still matches on name rather than
     * patron_id — older records predate that link, and a name match is
     * what stays consistent across all of them.
     */
    public static function wasFoundingAngel(string $firstName, string $lastName): bool
    {
        return static::whereRaw('LOWER(first_name) = ? AND LOWER(last_name) = ?', [
            strtolower($firstName), strtolower($lastName),
        ])->where('founding_angel', true)->exists();
    }
}
