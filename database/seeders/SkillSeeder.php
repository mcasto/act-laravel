<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $skills = [
            'Act',
            'Direct',
            'Sing',
            'Dance',
            'Play a musical instrument (which?)',
            'Costumes [finding, sewing]',
            'Makeup',
            'Hair',
            'Set design',
            'Set construction',
            'Set painting',
            'Props',
            'Stage Manager',
            'Sound & Lights Operator',
            'Sound & Lights Engineer',
            'Ticket selling',
            'Ticket taker at door',
            'Hospitality: Greeter at door, decorating, cleanup after performances',
            'Photography (still and/or video)',
            'Choreography',
            'Publicity',
            'Marketing',
            'Fund Raising',
            'Bartender',
            'Board member',
            'CASH Donor',
        ];

        foreach ($skills as $skill) {
            Skill::create(['name' => $skill]);
        }
    }
}
