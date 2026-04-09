<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PermissionLevelSeeder::class,
            PaymentMethodSeeder::class,
            DonationLevelSeeder::class,
            ShowSeeder::class,
            SiteConfigSeeder::class,
            FixrWebhookResponseSeeder::class,
            DonationSeeder::class,
            DonationPerkSeeder::class,
            TicketSeeder::class,
            ContactSeeder::class,
            SkillSeeder::class,
            VolunteerSeeder::class,
            VolunteerSkillSeeder::class,
            UserPermissionSeeder::class,
            AuditionSeeder::class,
            CourseSeeder::class,
            StandardButtonSeeder::class
        ]);
    }
}
