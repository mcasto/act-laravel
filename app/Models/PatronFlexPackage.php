<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatronFlexPackage extends Model
{
    protected $fillable = [
        'patron_id',
        'season',
        'tickets_purchased',
        'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function ticketSales(): HasMany
    {
        return $this->hasMany(TicketSale::class);
    }

    public function ticketsRemaining(): int
    {
        return $this->tickets_purchased - $this->ticketSales()->sum('quantity');
    }
}
