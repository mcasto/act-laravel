<?php

namespace App\Models;

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
        [$startYear] = explode('-', $this->season);
        $startYear = (int) ('20' . $startYear);

        $used = TicketSale::where('patron_id', $this->patron_id)
            ->whereHas('paymentMethod', fn ($q) => $q->where('value', 'flex'))
            ->whereHas('performance', fn ($q) => $q->whereBetween('date', [
                "{$startYear}-10-01",
                ($startYear + 1) . '-08-31',
            ]))
            ->sum('quantity');

        return $this->tickets_purchased - $used;
    }
}
