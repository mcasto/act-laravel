<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A role on the audition page. `notes` is the role description;
 * gender_age is free text ("Woman, over 50").
 */
class AuditionRole extends Model
{
    /** @use HasFactory<\Database\Factories\AuditionRoleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'audition_id',
        'name',
        'gender_age',
        'notes',
        'sort_order',
    ];
}
