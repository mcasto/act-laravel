<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Show extends Model
{
    /** @use HasFactory<\Database\Factories\ShowFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'writer',
        'tagline',
        'director',
        'info',
        'poster',
        'ticket_sales_start',
        'slug',
        'tentative'
    ];

    public static function validate($data, $id = null)
    {
        $rules = [
            'name'               => ['required', 'string', 'max:255'],
            'writer'             => ['required', 'string', 'max:255'],
            'tagline'            => ['required', 'string', 'max:255'],
            'director'           => ['required', 'string', 'max:255'],
            'info'               => ['nullable', 'string', 'max:65535'],
            'poster'             => ['required', 'string', 'max:255'],
            'ticket_sales_start' => ['required', 'date'],
            'slug'               => ['required', 'string', 'max:255'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()->toArray()];
        }

        $validated = $validator->validated();
        if (is_null($validated['info'])) {
            $validated['info'] = "";
        }

        return $validated;
    }

    /**
     * Relationship to gallery_images
     */
    public function galleryImages()
    {
        return $this->hasMany(GalleryImage::class);
    }

    /**
     * Relationship to performances
     */
    public function performances()
    {
        return $this->hasMany(Performance::class)->orderBy('date');
    }
}
