<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class StandardButton extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget('standard-buttons'));
        static::deleted(fn() => Cache::forget('standard-buttons'));
    }

    protected $fillable = [
        'label',
        'key',
        'sort_order'
    ];
}
