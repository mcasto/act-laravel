<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use App\Models\Performance;
use App\Models\Show;
use Illuminate\Database\Seeder;

class ShowSeeder extends Seeder
{
    /**
     * Seed shows + performances + gallery_images
     */
    public function run(): void
    {
        $dataFile = dirname(__DIR__) . '/raw-data/shows.json';
        $shows    = json_decode(file_get_contents($dataFile), true);

        foreach ($shows as $show) {
            if (! isset($show['gallery'])) {
                dd($show);
            }
            $gallery      = $show['gallery'];
            $performances = $show['performances'];
            unset($show['gallery']);
            unset($show['performances']);

            $rec     = Show::create($show);
            $show_id = $rec->id;

            // foreach gallery, insert into GalleryImage
            foreach ($gallery as $image) {
                GalleryImage::create([
                    'show_id' => $show_id,
                    'image'   => $image,
                ]);
            }

            // foreach performance, insert into Performance
            foreach ($performances as $performance) {
                $rec = [
                    'show_id'         => $show_id,
                    'date'            => $performance['date'],
                    'start_time'      => $performance['start_time'],
                    'sold_out_target' => 50,
                ];

                Performance::create($rec);
            }
        }

        // tickets

        // pull raw data for tickets
        $ticketData = json_decode(file_get_contents(dirname(__DIR__) . "/raw-data/tickets/ticket-data.json"), true);

        // get performances
        // foreach performance, get date
        // find tickets with that performance_date
        // build ticket records for that performance date
    }
}
