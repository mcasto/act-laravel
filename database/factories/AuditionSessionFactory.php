<?php
namespace Database\Factories;

use App\Models\AuditionSession;
use Carbon\Carbon;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditionSession>
 */
class AuditionSessionFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $lastSession = AuditionSession::orderBy('session', 'desc')->first();

  return [
   'audition_id' => 1,
   'session'     => $lastSession
   ? Carbon::parse($lastSession->session)->addDay()
   : now()->addDay(), // fallback to tomorrow if no sessions exist
  ];
 }
}
