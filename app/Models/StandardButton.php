<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StandardButton extends Model
{
    protected $fillable = [
        'label',
        'key',
        'sort_order'
    ];
}
