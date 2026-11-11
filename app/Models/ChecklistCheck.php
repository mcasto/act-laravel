<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A checklist item checked off for a show (or one of its performances). */
class ChecklistCheck extends Model
{
    protected $fillable = ['checklist_item_id', 'show_id', 'performance_id', 'user_id', 'checked_at'];

    protected $casts = ['checked_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
