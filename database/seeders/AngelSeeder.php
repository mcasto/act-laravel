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
        // Truncate the angels table before reseeding
        Angel::truncate();

        $raw = json_decode(file_get_contents(dirname(__DIR__) . '/raw-data/angels/angels.json'), true);
        foreach ($raw['levels'] as $level) {
            // Extract the amount from the label, e.g., "Seraphim ($1,000+)" -> 1000
            if (!preg_match('/\(\$([0-9,]+)\+\)/', $level['label'], $m)) {
                throw new \Exception("Could not parse amount from label: " . $level['label']);
            }
            $amount = (int)str_replace(',', '', $m[1]);

            // Find the angel level by matching the minimum amount
            $angelLevel = AngelLevel::where('min_amount', $amount)->first();

            if (!$angelLevel) {
                throw new \Exception("Could not find angel level with min_amount: $amount");
            }

            foreach ($level['donors'] as $donor) {
                $rec = [
                    'angel_level_id' => $angelLevel->id,
                    'name' => str_replace("*", "", $donor),
                    'founding_angel' => str_contains($donor, '*')
                ];

                Angel::create($rec);
            }
        }
    }
}
