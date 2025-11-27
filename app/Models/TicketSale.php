<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSale extends Model
{
    protected $fillable = [
        'show',
        'performance',
        'performance_id',
        'sold_at',
        'quantity',
        'first_name',
        'last_name',
        'email',
        'mobile_number',
        'contact_preferences_user_response'
    ];

    /**
     * relationship to performance
     */
    public function performance()
    {
        return $this->belongsTo(Performance::class);
    }
}
