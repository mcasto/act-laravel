<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeedingTest extends TestCase
{
 /**
  * Test that all tables contain at least one record after seeding.
  */
 public function testTablesAreSeeded(): void
 {
  $tables = [
   'users', 'permission_levels', 'user_permissions', 'volunteers', 'skills', 'volunteer_skills',
   'shows', 'performances', 'tickets', 'patrons', 'donations', 'donation_levels',
   'patron_payment_ids', 'payment_methods', 'fixr_webhook_responses',
   'contacts', 'site_configs', 'gallery_images', 'mailchimp_lists', 'mailchimp_members',
  ];

  foreach ($tables as $table) {
   $count = DB::table($table)->count();
   $this->assertGreaterThan(0, $count, "Table '$table' is empty. Seeding might have failed.");
  }
 }

 /**
  * Test that relationships are correctly seeded.
  */
 public function testRelationshipsAreSeeded(): void
 {
  $relations = [
   ['user_permissions', 'user_id', 'users'],
   ['user_permissions', 'permission_level_id', 'permission_levels'],
   ['volunteer_skills', 'volunteer_id', 'volunteers'],
   ['volunteer_skills', 'skill_id', 'skills'],
   ['performances', 'show_id', 'shows'],
   ['tickets', 'performance_id', 'performances'],
   ['tickets', 'patron_id', 'patrons'],
   ['donations', 'patron_id', 'patrons'],
   ['donations', 'donation_level_id', 'donation_levels'],
   ['patron_payment_ids', 'patron_id', 'patrons'],
   ['patron_payment_ids', 'payment_method_id', 'payment_methods'],
   ['mailchimp_members', 'mailchimp_list_id', 'mailchimp_lists'],
  ];

  foreach ($relations as [$table, $column, $parentTable]) {
   $exists = DB::table($table)
    ->whereNotNull($column)
    ->whereIn($column, DB::table($parentTable)->pluck('id'))
    ->exists();

   $this->assertTrue($exists, "Invalid or missing foreign key in '$table'. Column '$column' must reference '$parentTable'.");
  }
 }
}
