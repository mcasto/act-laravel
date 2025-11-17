<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Angel extends Model
{
    protected $fillable = [
        'angel_level_id',
        'name',
        'founding_angel'
    ];

    public function angelLevel(): BelongsTo
    {
        return $this->belongsTo(AngelLevel::class);
    }
}
