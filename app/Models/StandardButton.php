<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class StandardButton extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'label',
        'key',
        'sort_order'
    ];
}
