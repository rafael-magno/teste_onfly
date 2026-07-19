<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private function loginToken(User $user): string
    {
        return $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('data.access_token');
    }

    public function test_a_user_can_refresh_their_token(): void
    {
        $user = User::factory()->create();
        $token = $this->loginToken($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/refresh');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);
        $response->assertJsonPath('data.token_type', 'bearer');
        $this->assertNotSame($token, $response->json('data.access_token'));
    }

    public function test_the_refreshed_token_authenticates_requests(): void
    {
        $user = User::factory()->create();
        $token = $this->loginToken($user);

        $newToken = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/refresh')
            ->json('data.access_token');

        $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/travel-orders')
            ->assertOk();
    }

    public function test_the_old_token_is_invalidated_after_refresh(): void
    {
        $user = User::factory()->create();
        $token = $this->loginToken($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/refresh')
            ->assertOk();

        Auth::forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/travel-orders')
            ->assertUnauthorized();
    }

    public function test_refresh_without_a_token_fails(): void
    {
        $this->postJson('/api/refresh')->assertUnauthorized();
    }
}
