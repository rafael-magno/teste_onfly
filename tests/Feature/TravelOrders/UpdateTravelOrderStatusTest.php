<?php

namespace Tests\Feature\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTravelOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_travel_order_status(): void
    {
        $travelOrder = TravelOrder::factory()->create();

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertUnauthorized();
    }

    public function test_admin_can_approve_a_requested_travel_order(): void
    {
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->create();
        $this->actingAs($admin, 'api');

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', TravelOrderStatus::Approved->value);
        $this->assertDatabaseHas('travel_orders', [
            'id' => $travelOrder->id,
            'status' => TravelOrderStatus::Approved->value,
        ]);
        $this->assertDatabaseHas('travel_order_status_histories', [
            'travel_order_id' => $travelOrder->id,
            'user_id' => $admin->id,
            'status' => TravelOrderStatus::Approved->value,
        ]);
    }

    public function test_admin_can_cancel_a_requested_travel_order(): void
    {
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->create();
        $this->actingAs($admin, 'api');

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'cancelled',
            'reason' => 'Viagem não é mais necessária',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', TravelOrderStatus::Cancelled->value);
        $this->assertDatabaseHas('travel_orders', [
            'id' => $travelOrder->id,
            'status' => TravelOrderStatus::Cancelled->value,
            'cancellation_reason' => 'Viagem não é mais necessária',
        ]);
        $this->assertDatabaseHas('travel_order_status_histories', [
            'travel_order_id' => $travelOrder->id,
            'user_id' => $admin->id,
            'status' => TravelOrderStatus::Cancelled->value,
        ]);
    }

    public function test_reason_is_required_when_cancelling(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'api');
        $travelOrder = TravelOrder::factory()->create();

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_admin_cannot_cancel_a_travel_order_that_is_already_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->approved()->create();
        $this->actingAs($admin, 'api');

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'cancelled',
            'reason' => 'Mudança de planos',
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseHas('travel_orders', [
            'id' => $travelOrder->id,
            'status' => TravelOrderStatus::Approved->value,
        ]);
    }

    public function test_no_transition_is_allowed_once_a_travel_order_is_cancelled(): void
    {
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->cancelled()->create();
        $this->actingAs($admin, 'api');

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(409);
    }

    public function test_a_regular_user_cannot_update_any_travel_order_status(): void
    {
        $user = User::factory()->create();
        $travelOrder = TravelOrder::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('travel_orders', [
            'id' => $travelOrder->id,
            'status' => TravelOrderStatus::Requested->value,
        ]);
    }

    public function test_the_requester_cannot_update_the_status_of_their_own_travel_order_even_as_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($admin)->create();
        $this->actingAs($admin, 'api');

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertForbidden();
    }

    public function test_status_must_be_approved_or_cancelled(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'api');
        $travelOrder = TravelOrder::factory()->create();

        $response = $this->patchJson("/api/travel-orders/{$travelOrder->id}/status", [
            'status' => 'requested',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_updating_status_of_a_nonexistent_travel_order_returns_404(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'api');

        $response = $this->patchJson('/api/travel-orders/999999/status', [
            'status' => 'approved',
        ]);

        $response->assertNotFound();
    }
}
