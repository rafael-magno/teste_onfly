<?php

namespace Database\Factories;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelOrder>
 */
class TravelOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departureDate = fake()->dateTimeBetween('+1 week', '+1 month');
        $returnDate = (clone $departureDate)->modify('+'.fake()->numberBetween(1, 14).' days');

        return [
            'user_id' => User::factory(),
            'destination_country' => fake()->country(),
            'destination_state' => fake()->state(),
            'destination_city' => fake()->city(),
            'departure_date' => $departureDate->format('Y-m-d'),
            'return_date' => $returnDate->format('Y-m-d'),
            'status' => TravelOrderStatus::Requested,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::Approved,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::Cancelled,
        ]);
    }
}
