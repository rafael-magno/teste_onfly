<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'my-secret-password']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'my-secret-password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.token_type', 'bearer');
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.user.email', $user->email);
        $response->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in', 'user']]);
    }

    public function test_the_issued_token_authenticates_subsequent_requests(): void
    {
        $user = User::factory()->create(['password' => 'my-secret-password']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'my-secret-password',
        ]);

        $token = $loginResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/travel-orders', [
                'destination_country' => 'Brasil',
                'destination_state' => 'SP',
                'destination_city' => 'São Paulo',
                'departure_date' => now()->addWeek()->toDateString(),
                'return_date' => now()->addWeeks(2)->toDateString(),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.requester.id', $user->id);
    }

    public function test_login_fails_with_an_incorrect_password(): void
    {
        $user = User::factory()->create(['password' => 'my-secret-password']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_fails_for_a_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $response->assertUnauthorized();
    }

    public function test_email_and_password_are_required(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}
