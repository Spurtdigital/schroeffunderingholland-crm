<?php

namespace Database\Factories;

use App\Enums\StockReservationStatus;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockReservation>
 */
class StockReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'quote_id' => Quote::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'status' => StockReservationStatus::Active,
        ];
    }

    /**
     * Indicate that the reservation has been released.
     */
    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockReservationStatus::Released,
        ]);
    }

    /**
     * Indicate that the reservation has been consumed.
     */
    public function consumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockReservationStatus::Consumed,
        ]);
    }
}
