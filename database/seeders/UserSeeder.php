<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
 /**
  * Run the database seeds.
  */
 public function run(): void
 {
  User::create([
   'name'     => 'Mike Casto',
   'email'    => 'castoware@gmail.com',
   'password' => password_hash('lovemeg0524', PASSWORD_DEFAULT),
  ]);
 }
}
