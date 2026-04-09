<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patron extends Model
{
    protected $fillable = [
        'last_name',
        'first_name',
        'phone',
        'email',
    ];

    public function flexPackages(): HasMany
    {
        return $this->hasMany(PatronFlexPackage::class);
    }

    public function ticketSales(): HasMany
    {
        return $this->hasMany(TicketSale::class);
    }
}
