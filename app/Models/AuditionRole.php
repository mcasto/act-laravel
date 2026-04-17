<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditionRole extends Model
{
    /** @use HasFactory<\Database\Factories\AuditionRoleFactory> */
    use HasFactory, SoftDeletes;
}
