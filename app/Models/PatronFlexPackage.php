<?php

namespace App\Models;

use App\Helpers\TheaterSeason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function ticketsRemaining(): int
    {
        $dates = TheaterSeason::datesForSeason($this->season);

        $used = TicketSale::where('patron_id', $this->patron_id)
            ->whereHas('paymentMethod', fn ($q) => $q->where('value', 'flex'))
            ->whereHas('performance', fn ($q) => $q->whereBetween('date', [$dates['start'], $dates['end']]))
            ->sum('quantity');

        return $this->tickets_purchased - $used;
    }
}
