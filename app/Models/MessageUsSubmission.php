<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageUsSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'body',
    ];
}
