<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingFlexRedemption extends Model
{
    protected $fillable = [
        'token',
        'patron_id',
        'performance_id',
        'quantity',
        'first_name',
        'last_name',
        'phone',
        'special_request',
        'expires_at',
        'confirmed_at',
        'cancelled_at',
        'ticket_sale_id',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    public function ticketSale(): BelongsTo
    {
        return $this->belongsTo(TicketSale::class);
    }

    public function status(): string
    {
        return match (true) {
            $this->confirmed_at !== null => 'confirmed',
            $this->cancelled_at !== null => 'cancelled',
            $this->expires_at->isPast()  => 'expired',
            default                      => 'pending',
        };
    }
}
