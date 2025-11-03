<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    /** @use HasFactory<\Database\Factories\GalleryImageFactory> */
    use HasFactory;

    protected $fillable = [
        'show_id',
        'image',
        'sort_order'
    ];

    protected static function booted()
    {
        static::addGlobalScope('sortOrder', function ($query) {
            $query->orderBy('sort_order');
        });
    }

    public static function validate($data)
    {
        $validator = validator($data, [
            'show_id' => ['required', 'exists:shows,id'],
            'image'   => ['required', 'string', 'max:255'], // Assuming image is stored as a path/URL
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return $validator->validated();
    }

    /**
     * Relationship to shows
     */
    public function show()
    {
        return $this->belongsTo(Show::class);
    }
}
