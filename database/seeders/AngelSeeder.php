<?php

namespace Database\Seeders;

use App\Models\Angel;
use App\Models\AngelLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AngelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $raw = json_decode(file_get_contents(dirname(__DIR__) . '/raw-data/angels/angels.json'), true);
        foreach ($raw['levels'] as $level) {
            preg_match("/([\sa-zA-Z]+)\s\(/", $level['label'], $m);
            $label = $m[1];
            $level_id = AngelLevel::where('label', $label)->first()->id;
            foreach ($level['donors'] as $donor) {
                $rec = [
                    'angel_level_id' => $level_id,
                    'name' => str_replace("*", "", $donor),
                    'founding_angel' => stristr($donor, '*') == '*'
                ];

                Angel::create($rec);
            }
        }
    }
}
