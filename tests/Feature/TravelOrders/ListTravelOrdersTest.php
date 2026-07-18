<?php

namespace Tests\Feature\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTravelOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_travel_orders(): void
    {
        $response = $this->getJson('/api/travel-orders');

        $response->assertUnauthorized();
    }

    public function test_a_user_only_sees_their_own_travel_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        TravelOrder::factory()->for($user)->count(2)->create();
        TravelOrder::factory()->for($otherUser)->count(3)->create();
        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/travel-orders');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        collect($response->json('data'))->each(
            fn (array $order) => $this->assertSame($user->id, $order['requester']['id'])
        );
    }

    public function test_an_admin_sees_travel_orders_from_every_user(): void
    {
        $admin = User::factory()->admin()->create();
        TravelOrder::factory()->count(4)->create();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/travel-orders');

        $response->assertOk();
        $response->assertJsonCount(4, 'data');
    }

    public function test_it_filters_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        TravelOrder::factory()->count(2)->create();
        TravelOrder::factory()->approved()->count(3)->create();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/travel-orders?status=approved');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        collect($response->json('data'))->each(
            fn (array $order) => $this->assertSame(TravelOrderStatus::Approved->value, $order['status'])
        );
    }

    public function test_it_filters_by_destination_city(): void
    {
        $admin = User::factory()->admin()->create();
        TravelOrder::factory()->create(['destination_city' => 'Curitiba']);
        TravelOrder::factory()->count(2)->create(['destination_city' => 'São Paulo']);
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/travel-orders?destination_city=Paulo');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_it_filters_by_destination_state(): void
    {
        $admin = User::factory()->admin()->create();
        TravelOrder::factory()->create(['destination_state' => 'PR']);
        TravelOrder::factory()->count(2)->create(['destination_state' => 'SP']);
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/travel-orders?destination_state=SP');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_it_filters_by_destination_country(): void
    {
        $admin = User::factory()->admin()->create();
        TravelOrder::factory()->create(['destination_country' => 'Argentina']);
        TravelOrder::factory()->count(2)->create(['destination_country' => 'Brasil']);
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/travel-orders?destination_country=Brasil');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_it_filters_by_departure_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        TravelOrder::factory()->create([
            'departure_date' => now()->addMonths(3)->toDateString(),
            'return_date' => now()->addMonths(3)->addDays(5)->toDateString(),
        ]);
        $withinRange = TravelOrder::factory()->create([
            'departure_date' => now()->addWeek()->toDateString(),
            'return_date' => now()->addWeek()->addDays(5)->toDateString(),
        ]);
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/travel-orders?'.http_build_query([
            'departure_from' => now()->toDateString(),
            'departure_to' => now()->addMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $withinRange->id);
    }

    public function test_it_paginates_results(): void
    {
        $admin = User::factory()->admin()->create();
        TravelOrder::factory()->count(5)->create();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/travel-orders?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 5);
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $response = $this->getJson('/api/travel-orders?status=invalid');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);
    }
}
