<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class AngelLevel extends Model
{
    protected $fillable = [
        'label',
        'min_amount'
    ];

    protected $appends = ['min_amount_formatted', 'benefits', 'buttons'];

    public function angels(): HasMany
    {
        return $this->hasMany(Angel::class);
    }

    protected function minAmountFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() => '$' . number_format($this->attributes['min_amount'], 2, '.', ',')
        );
    }

    protected function buttons(): Attribute
    {
        $buttons = StandardButton::orderBy('sort_order')
            ->get()
            ->map(function ($rec) {
                $rec->popupText = view("standard-buttons.{$rec->key}", [
                    'price' => $this->min_amount_formatted
                ])->render();

                return $rec;
            });

        return Attribute::make(
            get: fn() => $buttons
        );
    }

    public function benefits(): Attribute
    {
        $id = $this->id;
        return Attribute::make(
            get: fn() => json_decode(Storage::disk('local')
                ->get("angel-config/{$id}.json"))
        );
    }
}
