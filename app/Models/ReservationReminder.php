<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One reservation reminder (logged or sent) for one patron for one
 * performance — see App\Console\Commands\SendReservationReminders.
 */
class ReservationReminder extends Model
{
    public const BATCH_DAY_BEFORE = 'day_before';
    public const BATCH_SAME_DAY = 'same_day';

    protected $fillable = [
        'performance_id',
        'patron_id',
        'email',
        'batch',
        'quantity',
        'status',
        'error',
    ];

    public function performance()
    {
        return $this->belongsTo(Performance::class);
    }

    public function patron()
    {
        return $this->belongsTo(Patron::class);
    }
}
