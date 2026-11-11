<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** On/off for one email — see App\Support\EmailTemplates. */
class EmailTemplate extends Model
{
    protected $fillable = ['key', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
