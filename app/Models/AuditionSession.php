<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One audition (or callback, when is_callback) session: `session` is the
 * start date+time, end_time the end on the same day.
 */
class AuditionSession extends Model
{
    /** @use HasFactory<\Database\Factories\AuditionSessionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'audition_id',
        'session',
        'end_time',
        'note',
        'is_callback',
    ];

    protected $casts = [
        'session' => 'datetime:Y-m-d H:i:s',
        'is_callback' => 'boolean',
    ];

    public function audition()
    {
        return $this->belongsTo(Audition::class);
    }
}
