<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSession extends Model
{
    /** @use HasFactory<\Database\Factories\CourseSessionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'date',
        'start',
        'end',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
