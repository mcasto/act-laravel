<?php

namespace App\Models;

use App\Helpers\TheaterSeason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatronFlexPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patron_id',
        'season',
        'tickets_purchased',
        'payment_method_id',
        'purchased_at',
        'comments',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Current-season flex balance for a patron — 0 when they have no
     * package (or no patron record at all). Used by the public flex
     * redemption flow, which has to enforce this server-side.
     */
    public static function remainingForPatron(?Patron $patron): int
    {
        if (! $patron) {
            return 0;
        }

        return self::where('patron_id', $patron->id)
            ->where('season', TheaterSeason::currentString())
            ->first()
            ?->ticketsRemaining() ?? 0;
    }

    public function ticketsRemaining(): int
    {
        $dates = TheaterSeason::datesForSeason($this->season);

        $totalPurchased = PatronFlexPackage::where('patron_id', $this->patron_id)
            ->where('season', $this->season)
            ->sum('tickets_purchased');

        $used = TicketSale::where('patron_id', $this->patron_id)
            ->whereHas('paymentMethod', fn ($q) => $q->where('value', 'flex'))
            ->whereHas('performance', fn ($q) => $q->whereBetween('date', [$dates['start'], $dates['end']]))
            ->sum('quantity');

        return $totalPurchased - $used;
    }
}
