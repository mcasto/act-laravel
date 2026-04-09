<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CompTicket extends Model
{
    protected $fillable = [
        'show_id',
        'uid',
        'name',
        'email',
        'sent_at',
        'performance_id',
        'pickup_name',
        'redeemed_at',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CompTicket $compTicket) {
            $compTicket->uid ??= (string) Str::uuid();
        });
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }
}
