<?php
namespace Database\Seeders;

use App\Models\GalleryImage;
use App\Models\Show;
use Illuminate\Database\Seeder;

class GalleryImageSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  $shows = Show::all()->toArray();
  foreach ($shows as $show) {
   $galleryImages = glob(storage_path() . "/app/public/images/gallery-*");
   $images        = array_filter($galleryImages, function ($image) use ($show) {
    return stristr($image, "gallery-{$show['id']}");
   });

   foreach ($images as $image) {
    GalleryImage::insert([
     'show_id' => $show['id'],
     'image'   => basename($image),
    ]);
   }
  }

 }
}
