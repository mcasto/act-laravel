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
        'cancelled',
        'cancelled_returned',
        'resold',
        'confirmed',
        'reason_changed',
        'special_seating',
        'front_row',
        'allow_angel_benefits',
        'door_last',
        'door_first',
        'comments',
    ];

    protected $casts = [
        'no_show' => 'integer',
        'cancelled' => 'integer',
        'cancelled_returned' => 'integer',
        'resold' => 'integer',
        'confirmed' => 'boolean',
        'front_row' => 'integer',
        'allow_angel_benefits' => 'boolean',
    ];

    protected $appends = ['active_quantity', 'used_quantity', 'held_quantity'];

    /**
     * Seats this sale takes out of the performance's seat count: quantity
     * minus cancelled tickets that were resold. A cancelled ticket that
     * wasn't resold still holds its seat. Use this for seats remaining and
     * sold out; use active_quantity for who's coming (confirmed). SQL sums
     * use self::HELD_QUANTITY_SQL.
     */
    public const HELD_QUANTITY_SQL = 'CASE WHEN quantity > resold THEN quantity - resold ELSE 0 END';

    public function getHeldQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->resold);
    }

    /**
     * Tickets still coming: quantity minus cancelled tickets ("confirmed").
     * Use this for attendance, revenue and reminders; seat counts use
     * held_quantity, Flex usage used_quantity. SQL sums use
     * self::ACTIVE_QUANTITY_SQL.
     */
    public const ACTIVE_QUANTITY_SQL = 'CASE WHEN quantity > cancelled THEN quantity - cancelled ELSE 0 END';

    public function getActiveQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->cancelled);
    }

    /**
     * Tickets counted against a Flex balance (or a patron's comp): every
     * ticket except cancelled ones that were returned. A cancelled ticket
     * that wasn't returned still counts as used. SQL sums use
     * self::USED_QUANTITY_SQL.
     */
    public const USED_QUANTITY_SQL = 'CASE WHEN quantity > cancelled_returned THEN quantity - cancelled_returned ELSE 0 END';

    public function getUsedQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->cancelled_returned);
    }

    /** Sales that still hold at least one seat. */
    public function scopeNotFullyCancelled($query)
    {
        return $query->whereColumn('cancelled', '<', 'quantity');
    }

    /**
     * Ticket rows have no soft-delete column (reconcileTicketCount() below
     * hard-deletes them too), and a soft delete never triggers the DB's
     * ON DELETE CASCADE, so remove them here along with the sale.
     */
    protected static function booted()
    {
        static::deleting(function (TicketSale $sale) {
            $sale->tickets()->delete();
        });
    }

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
