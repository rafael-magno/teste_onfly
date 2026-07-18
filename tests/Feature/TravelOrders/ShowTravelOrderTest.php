<?php

namespace Tests\Feature\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_a_travel_order(): void
    {
        $travelOrder = TravelOrder::factory()->create();

        $response = $this->getJson("/api/travel-orders/{$travelOrder->id}");

        $response->assertUnauthorized();
    }

    public function test_owner_can_view_their_own_travel_order(): void
    {
        $user = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();
        $this->actingAs($user, 'api');

        $response = $this->getJson("/api/travel-orders/{$travelOrder->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $travelOrder->id);
        $response->assertJsonPath('data.requester.id', $user->id);
    }

    public function test_admin_can_view_any_travel_order(): void
    {
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->create();
        $this->actingAs($admin, 'api');

        $response = $this->getJson("/api/travel-orders/{$travelOrder->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $travelOrder->id);
    }

    public function test_a_user_cannot_view_someone_elses_travel_order(): void
    {
        $user = User::factory()->create();
        $travelOrder = TravelOrder::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->getJson("/api/travel-orders/{$travelOrder->id}");

        $response->assertForbidden();
    }

    public function test_viewing_a_nonexistent_travel_order_returns_404(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $response = $this->getJson('/api/travel-orders/999999');

        $response->assertNotFound();
    }

    public function test_response_includes_status_change_history(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($owner)->approved()->create();
        $travelOrder->statusHistories()->create([
            'user_id' => $admin->id,
            'status' => TravelOrderStatus::Approved,
            'reason' => null,
        ]);
        $this->actingAs($owner, 'api');

        $response = $this->getJson("/api/travel-orders/{$travelOrder->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.status_histories');
        $response->assertJsonPath('data.status_histories.0.status', TravelOrderStatus::Approved->value);
        $response->assertJsonPath('data.status_histories.0.user.id', $admin->id);
    }
}
