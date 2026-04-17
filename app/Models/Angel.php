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
        'recognition_name',
        'last_name',
        'first_name',
        'benefit',
        'donation_amount',
        'payment_method_id',
        'founding_angel',
        'season',
    ];

    public function angelLevel(): BelongsTo
    {
        return $this->belongsTo(AngelLevel::class);
    }
}
