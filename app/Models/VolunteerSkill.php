<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerSkill extends Model
{
 /** @use HasFactory<\Database\Factories\VolunteerSkillFactory> */
 use HasFactory;

 protected $fillable = [
  'volunteer_id',
  'skill_id',
  'note',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'volunteer_id' => ['required', 'exists:volunteers,id'],
   'skill_id'     => ['required', 'exists:skills,id'],
   'note'         => ['nullable', 'string'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

 /**
  * Relationship to volunteers
  */
 public function volunteer()
 {
  return $this->belongsTo(Volunteer::class);
 }

 /**
  * Relationship to skills
  */
 public function skill()
 {
  return $this->belongsTo(Skill::class);
 }
}
