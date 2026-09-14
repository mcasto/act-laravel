<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_sale_id',
        'show_id',
        'number',
        'name',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    protected $appends = ['formatted_number'];

    public function ticketSale(): BelongsTo
    {
        return $this->belongsTo(TicketSale::class);
    }

    protected function formattedNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => str_pad((string) $this->number, 3, '0', STR_PAD_LEFT),
        );
    }
}
