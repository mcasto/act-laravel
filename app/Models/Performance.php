<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;

class Performance extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceFactory> */
    use HasFactory;

    protected $fillable = [
        'show_id',
        'date',
        'start_time',
        'sold_out',
        'sold_out_target',
        'fixr_link'
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
     * relationship to tickets
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * relationship to ticket sales
     */
    public function ticket_sales()
    {
        return $this->hasMany(TicketSale::class);
    }
}
