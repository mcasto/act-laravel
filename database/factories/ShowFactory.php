<?php
namespace Database\Factories;

use App\Models\Show;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

require_once __DIR__ . '/util/get-image-file.php';

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Show>
 */
class ShowFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $imagePath = storage_path('app/public/images');
  $imageFile = getImageFile($imagePath);

  $newImage = $imagePath . "/poster-" . uniqid() . "." . pathinfo($imageFile, PATHINFO_EXTENSION);
  rename($imageFile, $newImage);

  $name = fake()->words(rand(2, 4), true);
  $slug = Str::kebab($name);

  return [
   'name'               => $name,
   'writer'             => fake()->name(),
   'tagline'            => fake()->sentence(),
   'director'           => fake()->name(),
   'info'               => implode("\n\n", fake()->paragraphs(3)),
   'poster'             => basename($newImage),
   'ticket_sales_start' => fake()->dateTimeThisMonth()->format('Y-m-d'),
   'slug'               => $slug,
  ];
 }
}
