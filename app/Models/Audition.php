<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Audition extends Model
{
    /** @use HasFactory<\Database\Factories\AuditionFactory> */
    use HasFactory;

    protected $fillable = [
        'show_id',
        'display_date',
        'end_display_date',
    ];

    protected $appends = ['html'];

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Create HTML file when model is created
        static::created(function ($model) {
            if (isset($model->attributes['html'])) {
                $model->saveHtmlToFile($model->attributes['html']);
            }
        });

        // Update HTML file when model is updated
        static::updated(function ($model) {
            if (isset($model->attributes['html'])) {
                $model->saveHtmlToFile($model->attributes['html']);
            }
        });

        // Delete HTML file when model is deleted
        static::deleted(function ($model) {
            $model->deleteHtmlFile();
        });
    }

    /**
     * Save HTML content to file.
     *
     * @param string|null $htmlContent
     * @return bool
     */
    public function saveHtmlToFile($htmlContent = null)
    {
        if (!$this->id) {
            return false;
        }

        $filePath = "audition-html/{$this->id}.html";

        if ($htmlContent !== null) {
            $this->attributes['html'] = $htmlContent;
        }

        if (isset($this->attributes['html']) && !empty($this->attributes['html'])) {
            return Storage::disk('local')->put($filePath, $this->attributes['html']);
        } else {
            // Delete file if content is empty
            if (Storage::disk('local')->exists($filePath)) {
                return Storage::disk('local')->delete($filePath);
            }
            return true;
        }
    }

    /**
     * Delete the HTML file.
     */
    public function deleteHtmlFile()
    {
        $filePath = "audition-html/{$this->id}.html";
        if (Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->delete($filePath);
        }
        return true;
    }

    /**
     * Get the HTML attribute from file.
     *
     * @return string|null
     */
    public function getHtmlAttribute()
    {
        $filePath = "audition-html/{$this->id}.html";

        if (Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->get($filePath);
        }

        return null;
    }

    /**
     * Set the HTML attribute (doesn't save to database, only to file via events).
     *
     * @param  string  $value
     * @return void
     */
    public function setHtmlAttribute($value)
    {
        $this->attributes['html'] = $value;
    }

    /**
     * Create or update audition with HTML content.
     *
     * @param array $attributes
     * @return static
     */
    public static function createWithHtml(array $attributes)
    {
        $htmlContent = $attributes['html'] ?? null;
        unset($attributes['html']);

        $model = static::create($attributes);

        if ($htmlContent !== null) {
            $model->saveHtmlToFile($htmlContent);
        }

        return $model;
    }

    /**
     * Update audition with HTML content.
     *
     * @param array $attributes
     * @return bool
     */
    public function updateWithHtml(array $attributes)
    {
        $htmlContent = $attributes['html'] ?? null;
        unset($attributes['html']);

        $result = $this->update($attributes);

        if ($htmlContent !== null) {
            $this->saveHtmlToFile($htmlContent);
        }

        return $result;
    }
}
