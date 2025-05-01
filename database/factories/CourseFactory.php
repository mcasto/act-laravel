<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'             => 'Acting: Basics and Beyond',
            'instructor_name'  => 'Kit Thornton',
            'instructor_photo' => 'course-instructor-kit-thornton.jpeg',
            'instructor_email' => 'azuaycommunitytheater@gmail.com',
            'instructor_info'  => '<p>Kit is an ACT company member, professional educator, actor, director, and playwright.</p>',
            'enrollment_start' => Carbon::now()->subDay(5)->toDateString(),
            'enrollment_end'   => Carbon::now()->addDay(30)->toDateString(),
            'cost'             => 25,
            'poster'           => 'course-poster-acting-basics.png',
            'tagline'          => 'Learn stage acting and how to succeed as a theater company member',
            'location'         => "Azuay Community Theater, 14-46 Atonio Vega Muñoz between Estevez de Toral and Coronel Talbot",
            'slug'             => 'acting-basics-and-beyond-2025',
            'fixr_id'          => '540997370',
        ];
    }
}
