<?php
namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShowTest extends TestCase
{
 /**
  * Test the all-shows endpoint returns properly formatted data
  */
 public function testAllShows(): void
 {
  $response = $this->get('/api/all-shows');

  $response->assertJsonStructure([
   '*' => [ // Ensure each item in the array has this structure
    'name',
    'writer',
    'tagline',
    'director',
    'info',
    'poster',
    'ticket_sales_start',
    'slug',
    'performances',
    'gallery_images',
   ],
  ]);
 }

 /**
  * Test show creation (requires auth)
  */
 public function testShowCreation(): void
 {
  $name = fake()->words(3, true);

  $show = [
   'name'               => $name,
   'writer'             => fake()->name(),
   'tagline'            => fake()->sentence(),
   'director'           => fake()->name(),
   'info'               => fake()->paragraphs(3, true),
   'poster'             => fake()->uuid() . ".jpeg",
   'ticket_sales_start' => fake()->date(),
   'slug'               => Str::kebab("$name-" . Carbon::now()->year()),
  ];

  $response = $this->post('/api/create-show', $show, ['Authorization' => 'Bearer 2|CdUN5kXzBjOjt1LNJQXEAfNhlE7Qnia20DUQMDRX07879acf']);

  $response->assertJsonStructure([
   // Ensure each item in the array has this structure
   'name',
   'writer',
   'tagline',
   'director',
   'info',
   'poster',
   'ticket_sales_start',
   'slug',
  ],
  );
 }
}
