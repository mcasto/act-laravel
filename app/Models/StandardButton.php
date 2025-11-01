<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StandardButton extends Model
{
    protected $fillable = [
        'label',
        'key',
        'sort_order'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['popupText'];

    /**
     * Accessor for popupText - reads from file
     */
    public function getPopupTextAttribute()
    {
        $key = $this->attributes['key'] ?? null;

        if (!$key) {
            return null;
        }

        try {
            return Storage::disk('local')->get("standard-buttons/{$key}.html") ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mutator for popupText - writes to file
     */
    public function setPopupTextAttribute($value)
    {
        $key = $this->attributes['key'] ?? $this->key;

        if (!$key) {
            return;
        }

        if ($value !== null) {
            Storage::disk('local')->put("standard-buttons/{$key}.html", $value);
        } else {
            // Delete the file if popupText is set to null
            try {
                Storage::disk('local')->delete("standard-buttons/{$key}.html");
            } catch (\Exception $e) {
                // File doesn't exist or can't be deleted, ignore
            }
        }
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Update file when model is updated
        static::updating(function ($model) {
            if ($model->isDirty('popupText')) {
                $key = $model->getOriginal('key') ?? $model->key;
                $newKey = $model->key;

                // If key changed, we need to handle file renaming
                if ($key !== $newKey) {
                    // Delete old file if it exists
                    try {
                        Storage::disk('local')->delete("standard-buttons/{$key}.html");
                    } catch (\Exception $e) {
                        // Ignore if file doesn't exist
                    }
                }
            }
        });

        // Delete file when model is deleted
        static::deleting(function ($model) {
            $key = $model->key;
            if ($key) {
                try {
                    Storage::disk('local')->delete("standard-buttons/{$key}.html");
                } catch (\Exception $e) {
                    // Ignore if file doesn't exist
                }
            }
        });
    }
}
