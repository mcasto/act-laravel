<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AngelLevel extends Model
{
    protected $fillable = [
        'label',
        'min_amount'
    ];

    protected $appends = ['min_amount_formatted'];

    public function angels(): HasMany
    {
        return $this->hasMany(Angel::class);
    }

    protected function minAmountFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() => '$' . number_format($this->attributes['min_amount'], 2, '.', ',')
        );
    }
}
