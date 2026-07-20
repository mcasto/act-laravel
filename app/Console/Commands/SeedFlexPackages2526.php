<?php

namespace App\Console\Commands;

use App\Models\Patron;
use App\Models\PatronFlexPackage;
use Illuminate\Console\Command;

class SeedFlexPackages2526 extends Command
{
    protected $signature = 'flex:seed-2526';
    protected $description = 'One-off: seed 2025-2026 flex package data from the angel/flex master list';

    const SEASON = '25-26';

    // payment_methods.id
    const PM_FIXR     = 3;  // Credit Card (FixR)
    const PM_TRANSFER = 2;  // Ecuadorian Bank Transfer
    const PM_CASH     = 6;  // Cash
    const PM_PAYPAL   = 1;  // PayPal
    const PM_COMP     = 4;  // Comp Ticket

    // New patrons not yet in the database
    const NEW_PATRONS = [
        ['first_name' => 'Susan',          'last_name' => 'Connelly',        'email' => 'suzanneeconnelly@gmail.com'],
        ['first_name' => 'Gwendolyn',      'last_name' => 'Hemer',           'email' => 'gjhemer@gmail.com'],
        ['first_name' => 'Lori',           'last_name' => 'Holland',         'email' => 'lannwhite2002@yahoo.com'],
        ['first_name' => 'Teddy',          'last_name' => 'Jamieson',        'email' => 'hiway2guy@gmail.com'],
        ['first_name' => 'Scott',          'last_name' => 'Levine',          'email' => 'shlevine@gmail.com'],
        ['first_name' => 'John',           'last_name' => 'Olson',           'email' => 'olsonjohn01@gmail.com'],
        ['first_name' => 'Richard',        'last_name' => 'Patton',          'email' => 'karenbythesea@gmail.com'],
        ['first_name' => 'Michael',        'last_name' => 'Phillips',        'email' => 'michaelrphillips53@gmail.com'],
        ['first_name' => 'Holly',          'last_name' => 'Shrader',         'email' => 'shraderh@gmail.com'],
        ['first_name' => 'Frank & Carrie', 'last_name' => 'Valle',           'email' => 'cafra2024@gmail.com'],
        ['first_name' => 'Jamie',          'last_name' => 'Lawrence-Howard', 'email' => 'jamiehoward@me.com'],
        ['first_name' => 'Mary',           'last_name' => 'Gilliam',         'email' => 'gillianmaryeileen@gmail.com'],
        ['first_name' => 'Alexanne',       'last_name' => 'Stone',           'email' => 'alexanne13@gmail.com'],
    ];

    // [email, tickets_purchased, payment_method_id]
    // Mucklow appears twice: 12 from Seraphim level + 6 separate flex purchase
    const PACKAGES = [
        // ── Angel-level included flex ──────────────────────────────────────────
        ['sunni2018daze@gmail.com',      12, self::PM_TRANSFER],  // Andersen      – Seraphim Producer
        ['dinca.50@gmail.com',            6, self::PM_FIXR],      // Culp          – Guardian Angel
        ['otomandbob@gmail.com',          6, self::PM_CASH],      // Fry           – Guardian Angel
        ['amscert@gmail.com',            12, self::PM_CASH],      // Howe          – Seraphim Producer
        ['drryanjames@gmail.com',        12, self::PM_FIXR],      // James         – Guardian Angel + flex
        ['jamiehoward@me.com',            6, self::PM_PAYPAL],    // Lawrence-Howard – Guardian Angel
        ['singindabluze@gmail.com',       6, self::PM_TRANSFER],  // Deckard       – Guardian Angel
        ['kmuck58@gmail.com',            12, self::PM_FIXR],      // Mucklow       – Seraphim Producer (12)
        ['kthsr8225@gmail.com',          12, self::PM_TRANSFER],  // Osbourne      – Seraphim Producer
        ['alexanne13@gmail.com',          6, self::PM_FIXR],      // Stone         – Guardian Angel
        // ── Separate FLEX purchases ────────────────────────────────────────────
        ['runaroundsuervft@gmail.com',   12, self::PM_FIXR],      // Blumenfeld
        ['samcarothers7@gmail.com',      12, self::PM_FIXR],      // Carothers
        ['suzanneeconnelly@gmail.com',   12, self::PM_FIXR],      // Connelly
        ['kmf1925@aol.com',              12, self::PM_FIXR],      // Fiore
        ['lannwhite2002@yahoo.com',      12, self::PM_TRANSFER],  // Holland
        ['hiway2guy@gmail.com',           5, self::PM_FIXR],      // Jamieson
        ['cameronkayce@gmail.com',        5, self::PM_FIXR],      // Kayce
        ['shlevine@gmail.com',            6, self::PM_FIXR],      // Levine
        ['jmccaffertylany@gmail.com',     6, self::PM_FIXR],      // Mccafferty
        ['kmuck58@gmail.com',             6, self::PM_FIXR],      // Mucklow       – separate flex purchase (6)
        ['olsonjohn01@gmail.com',         6, self::PM_FIXR],      // Olson
        ['karenbythesea@gmail.com',       6, self::PM_FIXR],      // Patton
        ['michaelrphillips53@gmail.com',  6, self::PM_FIXR],      // Phillips
        ['ecpruitt63@gmail.com',         12, null],                // Pruitt        – no payment on file
        ['elpmas2@yahoo.com',            12, self::PM_TRANSFER],  // Sample
        ['rschlattmann777@gmail.com',     6, self::PM_FIXR],      // Schlattmann
        ['shear89692@gmail.com',          5, self::PM_FIXR],      // Shear
        ['ashields@gmail.com',            6, self::PM_FIXR],      // Shields
        ['shraderh@gmail.com',            5, self::PM_FIXR],      // Shrader
        ['stovallsh@gmail.com',           6, self::PM_FIXR],      // Stovall
        ['cafra2024@gmail.com',           5, self::PM_TRANSFER],  // Valle
        // ── Specials ──────────────────────────────────────────────────────────
        ['gjhemer@gmail.com',             2, null],                // Hemer         – credit from Jukebox
        ['robsrule@gmail.com',            6, null],                // Lander        – no payment on file
        ['gillianmaryeileen@gmail.com',   2, self::PM_COMP],      // Gilliam       – comp
    ];

    public function handle(): int
    {
        if (PatronFlexPackage::where('season', self::SEASON)->exists()) {
            $this->error('Flex packages for ' . self::SEASON . ' already exist. Aborting to prevent duplicates.');
            return 1;
        }

        $this->info('Creating missing patron records...');
        foreach (self::NEW_PATRONS as $data) {
            $patron = Patron::firstOrCreate(['email' => $data['email']], $data);
            $status = $patron->wasRecentlyCreated ? 'created' : 'already existed';
            $this->line("  [{$status}] {$patron->first_name} {$patron->last_name}");
        }

        $this->info('Creating flex packages...');
        $created = 0;
        $skipped = 0;

        foreach (self::PACKAGES as [$email, $tickets, $pmId]) {
            $patron = Patron::where('email', $email)->first();

            if (!$patron) {
                $this->error("  [SKIP] No patron found for {$email}");
                $skipped++;
                continue;
            }

            PatronFlexPackage::create([
                'patron_id'         => $patron->id,
                'season'            => self::SEASON,
                'tickets_purchased' => $tickets,
                'payment_method_id' => $pmId,
                'purchased_at'      => now(),
            ]);

            $this->line("  [OK] {$patron->first_name} {$patron->last_name} – {$tickets} tickets");
            $created++;
        }

        $this->info("Done. Packages created: {$created}" . ($skipped ? ", Skipped: {$skipped}" : ''));
        return 0;
    }
}
