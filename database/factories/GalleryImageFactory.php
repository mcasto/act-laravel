<?php
namespace Database\Factories;

use App\Models\Show;
use Illuminate\Database\Eloquent\Factories\Factory;

require_once __DIR__ . '/util/get-image-file.php';

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GalleryImage>
 */
class GalleryImageFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $id = Show::select('id')->inRandomOrder()->value('id');

  $imagePath = storage_path('app/public/images');
  $imageFile = getImageFile($imagePath);
  $newImage  = $imagePath . "/gallery-" . uniqid() . "." . pathinfo($imageFile, PATHINFO_EXTENSION);
  rename($imageFile, $newImage);

  return [
   'show_id' => $id,
   'image'   => basename($newImage),
  ];
 }
}
