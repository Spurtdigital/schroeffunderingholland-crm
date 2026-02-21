<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 50, 500);
        $salesPrice = round($purchasePrice * fake()->randomFloat(2, 1.3, 2.5), 2);

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(10),
            'sku' => strtoupper(fake()->unique()->bothify('PRD-####-???')),
            'purchase_price' => $purchasePrice,
            'sales_price' => $salesPrice,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'min_stock_level' => fake()->numberBetween(5, 20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product is low on stock.
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $minLevel = $attributes['min_stock_level'] ?? 10;
            return [
                'stock_quantity' => fake()->numberBetween(1, $minLevel),
            ];
        });
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
