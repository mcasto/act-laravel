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
}
