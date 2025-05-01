<?php
namespace Database\Seeders;

use App\Models\Audition;
use App\Models\AuditionRole;
use App\Models\AuditionSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditionSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  $audition                   = new Audition();
  $audition->show_id          = 18;
  $audition->display_date     = Carbon::now()->subDay(5)->toDateString();
  $audition->script           = parse_url(fake()->url(), PHP_URL_PATH);
  $audition->director_email   = fake()->email();
  $audition->first_read       = Carbon::now()->addDays(10)->toDateString();
  $audition->about            = fake()->paragraphs(3, true);
  $audition->rehearsals_start = Carbon::now()->addDays(15)->toDateString();

  $audition->save();

  $baseDate = now()->addDays(15);

  for ($i = 0; $i < 3; $i++) {
   AuditionSession::factory()->create([
    'audition_id' => 1,
    'session'     => $baseDate->copy()->addDays($i),
    'notes'       => "<p>" . implode("</p><p>", fake()->paragraphs(3)) . "</p>",
   ]);
  }

  AuditionRole::factory(rand(2, 5))->create();
 }
}
