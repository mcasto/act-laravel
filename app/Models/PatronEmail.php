<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One email address a patron uses. Every patron's main address
 * (patrons.email) has a row here too, kept in sync by Patron::booted().
 */
class PatronEmail extends Model
{
    protected $fillable = [
        'patron_id',
        'email',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = Patron::normalizeEmail($value);
    }
}
