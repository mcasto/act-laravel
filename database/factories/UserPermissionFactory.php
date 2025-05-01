<?php
namespace Database\Factories;

use App\Models\PermissionLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPermission>
 */
class UserPermissionFactory extends Factory
{
 /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
 public function definition(): array
 {
  $user  = User::select('id')->inRandomOrder()->value('id');
  $level = PermissionLevel::select('id')->inRandomOrder()->value('id');

  $rec = [
   'user_id'             => $user,
   'permission_level_id' => $level,
   'access'              => rand(1, 100) < 50 ? 'read-only' : 'full',
  ];

  return $rec;
 }
}
