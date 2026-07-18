<?php

namespace Tests\Feature\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_a_travel_order(): void
    {
        $response = $this->postJson('/api/travel-orders', [
            'destination_country' => 'Brasil',
            'destination_state' => 'SP',
            'destination_city' => 'São Paulo',
            'departure_date' => now()->addWeek()->toDateString(),
            'return_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_a_travel_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $payload = [
            'destination_country' => 'Brasil',
            'destination_state' => 'SP',
            'destination_city' => 'São Paulo',
            'departure_date' => now()->addWeek()->toDateString(),
            'return_date' => now()->addWeeks(2)->toDateString(),
        ];

        $response = $this->postJson('/api/travel-orders', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.destination_country', $payload['destination_country']);
        $response->assertJsonPath('data.destination_state', $payload['destination_state']);
        $response->assertJsonPath('data.destination_city', $payload['destination_city']);
        $response->assertJsonPath('data.departure_date', $payload['departure_date']);
        $response->assertJsonPath('data.return_date', $payload['return_date']);
        $response->assertJsonPath('data.status', TravelOrderStatus::Requested->value);
        $response->assertJsonPath('data.requester.id', $user->id);

        $this->assertDatabaseHas('travel_orders', [
            'user_id' => $user->id,
            'destination_country' => $payload['destination_country'],
            'destination_state' => $payload['destination_state'],
            'destination_city' => $payload['destination_city'],
            'status' => TravelOrderStatus::Requested->value,
        ]);

        $this->assertDatabaseCount('travel_order_status_histories', 1);

        $this->assertDatabaseHas('travel_order_status_histories', [
            'travel_order_id' => $response->json('data.id'),
            'user_id' => $user->id,
            'status' => TravelOrderStatus::Requested->value,
            'reason' => null,
        ]);
    }

    public function test_destination_state_is_optional(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $response = $this->postJson('/api/travel-orders', [
            'destination_country' => 'Japão',
            'destination_city' => 'Tóquio',
            'departure_date' => now()->addWeek()->toDateString(),
            'return_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.destination_state', null);
    }

    public function test_the_requester_is_always_the_authenticated_user_regardless_of_payload(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/travel-orders', [
            'user_id' => $otherUser->id,
            'destination_country' => 'Brasil',
            'destination_state' => 'RJ',
            'destination_city' => 'Rio de Janeiro',
            'departure_date' => now()->addWeek()->toDateString(),
            'return_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.requester.id', $user->id);
        $this->assertDatabaseMissing('travel_orders', ['user_id' => $otherUser->id]);
    }

    public function test_destination_country_destination_city_departure_date_and_return_date_are_required(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $response = $this->postJson('/api/travel-orders', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'destination_country',
            'destination_city',
            'departure_date',
            'return_date',
        ]);
    }

    public function test_return_date_must_be_after_departure_date(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $response = $this->postJson('/api/travel-orders', [
            'destination_country' => 'Brasil',
            'destination_state' => 'SP',
            'destination_city' => 'São Paulo',
            'departure_date' => now()->addWeeks(2)->toDateString(),
            'return_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['return_date']);
    }

    public function test_departure_date_cannot_be_in_the_past(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $response = $this->postJson('/api/travel-orders', [
            'destination_country' => 'Brasil',
            'destination_state' => 'SP',
            'destination_city' => 'São Paulo',
            'departure_date' => now()->subDay()->toDateString(),
            'return_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['departure_date']);
    }
}
