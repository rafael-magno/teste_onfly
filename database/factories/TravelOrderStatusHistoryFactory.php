<?php

namespace Database\Factories;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\TravelOrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelOrderStatusHistory>
 */
class TravelOrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_order_id' => TravelOrder::factory(),
            'user_id' => User::factory(),
            'status' => TravelOrderStatus::Requested,
            'reason' => null,
            'created_at' => now(),
        ];
    }
}
