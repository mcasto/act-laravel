<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'comments',
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

    /**
     * Texts of this angel's level benefits that apply at the concession
     * stand (drinks/snacks) — shown on the ticket sales print sheet and
     * the ticket sale form.
     */
    public function concessionBenefits(): array
    {
        return collect($this->angelLevel?->benefits ?? [])
            ->filter(fn ($benefit) => $benefit->concession ?? false)
            ->pluck('text')
            ->values()
            ->all();
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->patron?->email,
        );
    }
}
