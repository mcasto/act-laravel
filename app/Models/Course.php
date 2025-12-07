<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    /**
     * Append attributes to the model's array form
     */
    protected $appends = ['message'];

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
