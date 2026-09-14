<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patron_id',
        'payment_method_id',
        'transaction_id',
        'transfer_date',
        'performance_id',
        'sold_at',
        'quantity',
        'no_show',
        'confirmed',
        'reason_changed',
        'guest_list',
    ];

    protected $casts = [
        'no_show' => 'integer',
        'confirmed' => 'boolean',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Issues one internal ticket ID per unit of quantity, drawn from this
     * sale's show's shared sequence — the first named after the purchaser,
     * the rest defaulting to their own zero-padded number as a placeholder
     * name until an admin knows who they belong to.
     */
    public function issueTickets(string $purchaserName): void
    {
        $show = $this->performance->show;

        foreach ($show->reserveTicketNumbers($this->quantity) as $i => $number) {
            $this->tickets()->create([
                'show_id' => $show->id,
                'number' => $number,
                'name' => $i === 0 ? $purchaserName : str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            ]);
        }
    }

    /**
     * Reconciles this sale's ticket rows after a quantity change. Growing
     * appends newly-issued numbers continuing the show's sequence; shrinking
     * removes the highest-numbered tickets first, preserving any
     * already-customized names on the earlier ones. A no-op if the quantity
     * hasn't actually changed.
     */
    public function reconcileTicketCount(int $newQuantity): void
    {
        $current = $this->tickets()->count();

        if ($newQuantity > $current) {
            $show = $this->performance->show;

            foreach ($show->reserveTicketNumbers($newQuantity - $current) as $number) {
                $this->tickets()->create([
                    'show_id' => $show->id,
                    'number' => $number,
                    'name' => str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                ]);
            }
        } elseif ($newQuantity < $current) {
            $this->tickets()->orderByDesc('number')->limit($current - $newQuantity)->get()->each->delete();
        }
    }
}
