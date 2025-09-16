<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Volunteer extends Model
{
    /** @use HasFactory<\Database\Factories\VolunteerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'experience',
        'active',
    ];

    public static function validate($data)
    {
        $validator = validator($data, [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:volunteers,email'],
            'phone'      => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return $validator->validated();
    }

    /**
     * Relationship to volunteer_skills
     */
    public function volunteerSkills()
    {
        return $this->hasMany(VolunteerSkill::class)
            ->join('skills', 'volunteer_skills.skill_id', '=', 'skills.id')
            ->orderBy('skills.name')
            ->select('volunteer_skills.*');
    }
}
