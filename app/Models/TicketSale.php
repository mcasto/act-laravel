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
        'special_seating',
        'front_row',
        'door_last',
        'door_first',
        'comments',
    ];

    protected $casts = [
        'no_show' => 'integer',
        'confirmed' => 'boolean',
        'front_row' => 'integer',
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
     * sale's show's shared sequence. $names is a positional array of
     * admin-entered guest names from the "Tickets" section of the New
     * Ticket Sale form (may be shorter than quantity, or contain blank
     * entries for any guest not yet known) — index 0 falls back to the
     * purchaser's own name when left blank, every other index falls back
     * to its own zero-padded ticket number as a placeholder name until an
     * admin knows who it belongs to.
     */
    public function issueTickets(string $purchaserName, array $names = []): void
    {
        $show = $this->performance->show;

        foreach ($show->reserveTicketNumbers($this->quantity) as $i => $number) {
            $customName = trim((string) ($names[$i] ?? ''));
            $formattedNumber = str_pad((string) $number, 3, '0', STR_PAD_LEFT);

            $this->tickets()->create([
                'show_id' => $show->id,
                'number' => $number,
                'name' => $customName !== ''
                    ? $customName
                    : ($i === 0 ? $purchaserName : $formattedNumber),
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
