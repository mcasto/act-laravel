<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A show's audition preview link: /audition/preview/{uid}. The uid is
 * uniqid() prefixed with this row's id, set right after the row is created.
 */
class AuditionPreview extends Model
{
    protected $fillable = ['show_id'];

    protected static function booted()
    {
        static::created(function (AuditionPreview $preview) {
            $preview->uid = uniqid("{$preview->id}-");
            $preview->saveQuietly();
        });
    }

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    /** The show's preview row, created (with its uid) if it doesn't have one yet. */
    public static function forShow(int $showId): self
    {
        return static::firstOrCreate(['show_id' => $showId]);
    }
}
