<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An overdue checklist to-do that's been emailed about — see checklist:notify-overdue. */
class ChecklistAlert extends Model
{
    protected $fillable = ['checklist_item_id', 'show_id', 'performance_id', 'due_at', 'notified_at'];

    protected $casts = ['due_at' => 'datetime', 'notified_at' => 'datetime'];
}
