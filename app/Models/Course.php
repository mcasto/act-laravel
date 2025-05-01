<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
 use HasFactory;

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
