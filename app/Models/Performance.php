<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Performance extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'show_id',
        'date',
        'start_time',
        'sold_out',
        'sold_out_target',
        'fixr_link'
    ];

    protected $casts = [
        'sold_out' => 'integer',
    ];

    protected $appends = [
        'formatted_date',
        'formatted_time'
    ];

    public static function validate($data)
    {
        $validator = validator($data, [
            'id'              => ['integer', 'min:1'],
            'show_id'         => ['required', 'exists:shows,id'],
            'date'            => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i:s'],
            'sold_out'        => ['required', 'boolean'],
            // Optional override — null follows the global Site Config target.
            'sold_out_target' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'fixr_link' => ['sometimes', 'nullable', 'string']

        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()->toArray()];
        }

        return $validator->validated();
    }

    /**
     * Get the formatted date attribute
     */
    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->date)->format('M d, Y');
    }

    /**
     * Get the formatted time attribute
     */
    public function getFormattedTimeAttribute()
    {
        return Carbon::parse($this->start_time)->format('g:i A');
    }

    /**
     * Relationship to shows
     */
    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    /**
     * relationship to ticket sales
     */
    public function ticket_sales()
    {
        return $this->hasMany(TicketSale::class);
    }

    /**
     * The seat count this performance counts as full at: its own
     * sold_out_target when one is set, then its show's, otherwise the
     * global Default Sold Out Target from Site Config.
     */
    public function effectiveSoldOutTarget(): int
    {
        if ($this->sold_out_target !== null) {
            return (int) $this->sold_out_target;
        }

        $showTarget = $this->show?->sold_out_target;
        if ($showTarget !== null) {
            return (int) $showTarget;
        }

        $config = Cache::remember('site-config', 3600, fn() => SiteConfig::latest()->first());

        return (int) ($config?->sold_out_target ?? 0);
    }

    /**
     * Seat counts for this performance — the single source for the admin
     * seating summary (TicketSaleController::seatingSummary) and the
     * director page's sales cards (DirectorController::sales).
     *
     * - reserved: confirmed tickets — sold minus cancelled
     *   (TicketSale::active_quantity). Redeemed comps are included; each has
     *   its own mirrored TicketSale (see CompTixController::redeemComp()).
     * - held: seats taken — sold minus resold (TicketSale::held_quantity).
     *   A cancelled seat that wasn't resold is still taken.
     * - comps: how many of `reserved` are redeemed comps (a subset).
     * - front_row_total: each distinct patron's standing need
     *   (Patron::front_row, once per patron even with several sales here)
     *   plus each sale's own reserved-party count (TicketSale::front_row).
     * - remaining: seats left before sold_out_target (target − held),
     *   floored at 0.
     *
     * @return array{reserved: int, held: int, comps: int, front_row_total: int, sold_out_target: int, remaining: int}
     */
    public function salesSummary(): array
    {
        // A fully cancelled sale has no one coming, so its front-row need no
        // longer applies.
        $sales = TicketSale::where('performance_id', $this->id)
            ->notFullyCancelled()
            ->with('patron')
            ->get();

        $reserved = (int) $sales->sum('active_quantity');
        $held = $this->seatsHeld();

        $comps = (int) TicketSale::where('performance_id', $this->id)
            ->whereIn('id', CompTicket::whereNotNull('ticket_sale_id')->select('ticket_sale_id'))
            ->sum(DB::raw(TicketSale::ACTIVE_QUANTITY_SQL));

        $patronFrontRow = $sales->pluck('patron')->filter()->unique('id')->sum('front_row');
        $saleFrontRow = $sales->sum('front_row');

        return [
            'reserved'        => $reserved,
            'held'            => $held,
            'comps'           => $comps,
            'front_row_total' => (int) ($patronFrontRow + $saleFrontRow),
            'sold_out_target' => $this->effectiveSoldOutTarget(),
            'remaining'       => max(0, $this->effectiveSoldOutTarget() - $held),
        ];
    }

    /** Seats taken: sold minus resold (see TicketSale::held_quantity). */
    public function seatsHeld(): int
    {
        return (int) TicketSale::where('performance_id', $this->id)
            ->sum(DB::raw(TicketSale::HELD_QUANTITY_SQL));
    }

    /** Seats left before the sold-out target, floored at 0. */
    public function seatsRemaining(): int
    {
        return max(0, $this->effectiveSoldOutTarget() - $this->seatsHeld());
    }
}
