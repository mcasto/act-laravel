<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;

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
            'sold_out_target' => ['required', 'integer', 'min:1'],
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
     * Seat counts for this performance — the single source for both the
     * admin seating summary (TicketSaleController::seatingSummary) and the
     * director page's sales cards (DirectorController::sales).
     *
     * - reserved: SUM(quantity) across all ticket sales. Redeemed comps are
     *   included — each has its own mirrored TicketSale (see
     *   CompTixController::redeemComp()).
     * - comps: how many of `reserved` are redeemed comps (a subset, never
     *   added on top).
     * - front_row_total: each distinct patron's standing need
     *   (Patron::front_row, once per patron even with several sales here)
     *   plus each sale's own reserved-party count (TicketSale::front_row).
     * - remaining: seats left before sold_out_target, floored at 0.
     *
     * @return array{reserved: int, comps: int, front_row_total: int, sold_out_target: int, remaining: int}
     */
    public function salesSummary(): array
    {
        $sales = TicketSale::where('performance_id', $this->id)
            ->with('patron')
            ->get();

        $reserved = (int) $sales->sum('quantity');

        $comps = (int) TicketSale::where('performance_id', $this->id)
            ->whereIn('id', CompTicket::whereNotNull('ticket_sale_id')->select('ticket_sale_id'))
            ->sum('quantity');

        $patronFrontRow = $sales->pluck('patron')->filter()->unique('id')->sum('front_row');
        $saleFrontRow = $sales->sum('front_row');

        return [
            'reserved'        => $reserved,
            'comps'           => $comps,
            'front_row_total' => (int) ($patronFrontRow + $saleFrontRow),
            'sold_out_target' => (int) $this->sold_out_target,
            'remaining'       => max(0, (int) $this->sold_out_target - $reserved),
        ];
    }
}
