<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
 /** @use HasFactory<\Database\Factories\SkillFactory> */
 use HasFactory;

 protected $fillable = [
  'skill',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'skill' => ['required', 'string', 'max:255', 'unique:skills,skill'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray(); // Return errors if validation fails
  }

  return $validator->validated();
 }

 /**
  * Relationship to volunteer_skills
  */
 public function volunteerSkills()
 {
  return $this->hasMany(VolunteerSkill::class);
 }
}
