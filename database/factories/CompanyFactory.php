<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->randomElement(['kevin@spurt.digital', 'bob@schroeffunderingholland.nl']),
            'phone' => fake()->phoneNumber(),
            'postal_code' => fake()->postcode(),
            'house_number' => fake()->buildingNumber(),
            'city' => fake()->city(),
            'street' => fake()->streetName(),
            'moneybird_contact_id' => null,
        ];
    }

    /**
     * Indicate that the company is synced with Moneybird.
     */
    public function withMoneybird(): static
    {
        return $this->state(fn (array $attributes) => [
            'moneybird_contact_id' => (string) fake()->numberBetween(100000, 999999),
        ]);
    }
}
