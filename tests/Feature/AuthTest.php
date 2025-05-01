<?php
namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{

 private string $token;

 public function it_logs_in_and_gets_a_token()
 {
  // Send login request
  $response = $this->postJson('/api/login', [
   'email'    => 'castoware@gmail.com',
   'password' => 'lovemeg0524',
  ]);

  // Assert response is successful
  $response->assertStatus(200)->assertJsonStructure(['token']);

  // Store the token for later tests
  $this->token = $response->json('token');
 }

 public function it_fetches_user_info()
 {
  // Ensure login test runs first
  $this->it_logs_in_and_gets_a_token();

  // Send request to fetch user data
  $response = $this->withHeaders([
   'Authorization' => 'Bearer ' . $this->token,
  ])->getJson('/api/user');

  // Assert response is successful
  $response->assertStatus(200)->assertJsonStructure([
   'id', 'name', 'email',
  ]);
 }

 public function it_logs_out()
 {
  // Ensure login test runs first
  $this->it_logs_in_and_gets_a_token();

  // Send logout request
  $response = $this->withHeaders([
   'Authorization' => 'Bearer ' . $this->token,
  ])->postJson('/api/logout');

  // Assert response is successful
  $response->assertStatus(200)->assertJson(['message' => 'Logged out']);
 }
}
