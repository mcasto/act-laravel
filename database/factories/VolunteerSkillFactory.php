<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\Volunteer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VolunteerSkill>
 */
class VolunteerSkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $volunteer = Volunteer::select('id')->inRandomOrder()->value('id');
        $skill     = Skill::select('id')->inRandomOrder()->value('id');

        return [
            'volunteer_id' => $volunteer,
            'skill_id'     => $skill,

        ];
    }
}
