<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'instructor_name',
        'instructor_email',
        'enrollment_start',
        'enrollment_end',
        'cost',
        'poster',
        'tagline',
        'location',
        'fixr',
        'instructor_photo',
        'instructor_info',
    ];

    /**
     * Append attributes to the model's array form
     */
    protected $appends = [];

    /**
     * Boot the model and register event listeners
     */
    protected static function booted()
    {
        static::deleting(function ($course) {
            // Delete the blade template
            $templatePath = resource_path("views/courses/{$course->slug}.blade.php");
            if (file_exists($templatePath)) {
                unlink($templatePath);
            }

            // Delete the HTML snippet
            $snippetPath = storage_path("app/public/snippets/courses/{$course->slug}.html");
            if (file_exists($snippetPath)) {
                unlink($snippetPath);
            }
        });
    }

    /**
     * Get the rendered view for this course
     */
    public function getMessageAttribute()
    {
        return view("courses.{$this->slug}")->render();
    }

    /**
     * Relationship to sessions
     */
    public function sessions()
    {
        return $this->hasMany(CourseSession::class)->orderBy('date', 'asc');
    }

    /**
     * Relationship to contacts
     */
    public function contacts()
    {
        return $this->hasMany(CourseContact::class);
    }
}
