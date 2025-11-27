<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSale extends Model
{
    protected $fillable = [
        'show',
        'performance',
        'sold_at',
        'first_name',
        'last_name',
        'email',
        'mobile_number',
        'contact_preferences_user_response'
    ];
}
