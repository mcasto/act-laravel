<?php

namespace Database\Seeders;

use App\Models\Angel;
use App\Models\AngelLevel;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class AngelSeeder extends Seeder
{
    public function run(): void
    {
        Angel::truncate();

        // Build founding_angel lookup from DB export CSV
        $foundingByLastName = [];
        $foundingByRecognitionName = [];
        $dbCsv = database_path('temp/laravel_act---2026-03-19--09-10-42.csv');
        $dbHandle = fopen($dbCsv, 'r');
        fgetcsv($dbHandle); // skip header
        while (($row = fgetcsv($dbHandle)) !== false) {
            // columns: id, angel_level_id, last_name, first_name, recognition_name, benefit, donation_amount, payment_method_id, founding_angel
            $lastName        = trim($row[2]);
            $recognitionName = trim($row[4]);
            $foundingAngel   = (int) $row[8];

            if ($lastName !== '') {
                $foundingByLastName[$lastName] = $foundingAngel;
            } else {
                $foundingByRecognitionName[$recognitionName] = $foundingAngel;
            }
        }
        fclose($dbHandle);

        // Build AngelLevel map: min_amount → id
        $levelMap = AngelLevel::all()->keyBy('min_amount')->map(fn($l) => $l->id);

        // Build PaymentMethod map: value → id
        $pmMap = PaymentMethod::all()->keyBy('value')->map(fn($p) => $p->id);

        // Parse '25-'26 Angels CSV
        $angelsCsv = database_path("temp/'25-'26 Angels _ Flex - Master List - Angels Only.csv");
        $handle = fopen($angelsCsv, 'r');

        $rowIndex = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowIndex++;
            // Rows 1-3 are blank, row 4 is header — skip them
            if ($rowIndex <= 4) {
                continue;
            }

            $donationType = trim($row[8] ?? '');
            if ($donationType === '') {
                continue; // skip wheelchair/blank rows
            }

            $recognitionName = trim($row[1] ?? '');
            $lastName        = trim($row[2] ?? '');
            $firstName       = trim($row[3] ?? '');
            $benefit         = trim($row[4] ?? '');
            $donationRaw     = trim($row[6] ?? '');
            $paymentType     = trim($row[9] ?? '');

            // Construct recognition_name if blank
            if ($recognitionName === '') {
                $recognitionName = trim("$firstName $lastName");
            }

            // Parse donation amount
            $donationAmount = (float) preg_replace('/[^0-9.]/', '', $donationRaw);

            // Resolve angel_level_id
            $minAmount = match (true) {
                str_contains($donationType, 'Seraphim') => 1000,
                str_contains($donationType, 'Guardian') => 500,
                str_contains($donationType, 'Archangel') => 250,
                default => 100,
            };
            $angelLevelId = $levelMap[$minAmount] ?? null;

            // Resolve payment_method_id
            $pmValue = match (true) {
                str_contains($paymentType, 'FixR') => 'credit_card',
                str_contains($paymentType, 'Bank Transfer') => 'transfer',
                str_contains($paymentType, 'PayPal') => 'paypal',
                str_contains($paymentType, 'Cash') => 'cash',
                str_contains($paymentType, 'Trade') => 'trade',
                default => null,
            };
            $paymentMethodId = $pmValue ? ($pmMap[$pmValue] ?? null) : null;

            // Resolve founding_angel
            if ($lastName !== '' && array_key_exists($lastName, $foundingByLastName)) {
                $foundingAngel = $foundingByLastName[$lastName];
            } elseif (array_key_exists($recognitionName, $foundingByRecognitionName)) {
                $foundingAngel = $foundingByRecognitionName[$recognitionName];
            } else {
                $foundingAngel = 0;
            }

            Angel::create([
                'angel_level_id'    => $angelLevelId,
                'recognition_name'  => $recognitionName,
                'last_name'         => $lastName,
                'first_name'        => $firstName,
                'benefit'           => $benefit,
                'donation_amount'   => $donationAmount,
                'payment_method_id' => $paymentMethodId,
                'founding_angel'    => $foundingAngel,
                'season'            => '25-26',
            ]);
        }

        fclose($handle);
    }
}
