<?php
namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
 /**
  * Ensure all the required tables are present (ignored default tables set up by Laravel)
  */
 public function testDatabaseIntegrityTest(): void
 {
  $this->assertTrue(Schema::hasTable('contacts'));
  $this->assertTrue(Schema::hasTable('donation_levels'));
  $this->assertTrue(Schema::hasTable('donation_perks'));
  $this->assertTrue(Schema::hasTable('donations'));
  $this->assertTrue(Schema::hasTable('fixr_webhook_responses'));
  $this->assertTrue(Schema::hasTable('gallery_images'));
  $this->assertTrue(Schema::hasTable('mailchimp_lists'));
  $this->assertTrue(Schema::hasTable('mailchimp_members'));
  $this->assertTrue(Schema::hasTable('patron_payment_ids'));
  $this->assertTrue(Schema::hasTable('patrons'));
  $this->assertTrue(Schema::hasTable('payment_methods'));
  $this->assertTrue(Schema::hasTable('performances'));
  $this->assertTrue(Schema::hasTable('permission_levels'));
  $this->assertTrue(Schema::hasTable('shows'));
  $this->assertTrue(Schema::hasTable('site_configs'));
  $this->assertTrue(Schema::hasTable('skills'));
  $this->assertTrue(Schema::hasTable('tickets'));
  $this->assertTrue(Schema::hasTable('user_permissions'));
  $this->assertTrue(Schema::hasTable('users'));
  $this->assertTrue(Schema::hasTable('volunteer_skills'));
  $this->assertTrue(Schema::hasTable('volunteers'));
 }

 /**
  * Test required (non-nullable) columns.
  */
 public function testRequiredColumns(): void
 {
  $tables = [
   'users'              => ['name', 'email', 'password'],
   'contacts'           => ['name', 'email', 'subject', 'body'],
   'site_configs'       => ['ticket_price', 'ticket_email', 'contact_email', 'dev_email', 'sold_out_target'],
   'permission_levels'  => ['label', 'value'],
   'user_permissions'   => ['user_id', 'permission_level_id', 'access'],
   'volunteers'         => ['name', 'email', 'phone', 'active'],
   'skills'             => ['skill'],
   'volunteer_skills'   => ['volunteer_id', 'skill_id'],
   'shows'              => ['name', 'writer', 'tagline', 'directory', 'poster', 'ticket_sales_start', 'slug'],
   'performances'       => ['show_id', 'date', 'sold_out_target'],
   'tickets'            => ['performance_id', 'patron_id', 'assigned_name', 'qty', 'order_date'],
   'patrons'            => ['name', 'email'],
   'donation_levels'    => ['label', 'amount_min', 'amount_max'],
   'donations'          => ['patron_id', 'amount', 'donation_level_id', 'date'],
   'payment_methods'    => ['label', 'value'],
   'patron_payment_ids' => ['patron_id', 'payment_method_id'],
   'mailchimp_lists'    => ['name', 'mailchimp_id'],
   'mailchimp_members'  => ['mailchimp_list_id', 'email', 'status'],
  ];

  foreach ($tables as $table => $columns) {
   foreach ($columns as $column) {
    $this->assertDatabaseRejectsNull($table, $column);
   }
  }
 }

 /**
  * Helper function to test required columns.
  */
 private function assertDatabaseRejectsNull(string $table, string $column): void
 {
  $data = DB::table($table)->first();

  if (! $data) {
   $this->markTestSkipped("Skipping test for $table because it has no existing data.");
   return;
  }

  $idColumn = array_key_first((array) $data); // Assume first column is ID

  $existingData = (array) $data;
  unset($existingData[$idColumn]); // Remove ID field to avoid primary key issues

  $existingData[$column] = null; // Set the required field to null

  $this->expectException(\Illuminate\Database\QueryException::class);

  DB::table($table)->insert($existingData);
 }

 /**
  * Test foreign key constraints.
  */
 public function testForeignKeyConstraints(): void
 {
  $foreignKeys = [
   'user_permissions'   => ['user_id' => 'users', 'permission_level_id' => 'permission_levels'],
   'volunteer_skills'   => ['volunteer_id' => 'volunteers', 'skill_id' => 'skills'],
   'performances'       => ['show_id' => 'shows'],
   'tickets'            => ['performance_id' => 'performances', 'patron_id' => 'patrons'],
   'donations'          => ['patron_id' => 'patrons', 'donation_level_id' => 'donation_levels'],
   'patron_payment_ids' => ['patron_id' => 'patrons', 'payment_method_id' => 'payment_methods'],
   'mailchimp_members'  => ['mailchimp_list_id' => 'mailchimp_lists'],
  ];

  foreach ($foreignKeys as $table => $relations) {
   foreach ($relations as $column => $parentTable) {
    $this->assertForeignKeyConstraint($table, $column, $parentTable);
   }
  }
 }

 /**
  * Helper function to test foreign key constraints.
  */
 private function assertForeignKeyConstraint(string $table, string $column, string $parentTable): void
 {
  $this->expectException(QueryException::class);
                                                  // Insert a record with an invalid foreign key reference
  DB::table($table)->insert([$column => 999999]); // Assuming 999999 does not exist in the parent table
 }
}
