<?php

namespace Database\Factories;

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 10, 500);
        $total = round($quantity * $unitPrice, 2);

        return [
            'quote_id' => Quote::factory(),
            'itemable_type' => null,
            'itemable_id' => null,
            'type' => QuoteItemType::FreeLine,
            'description' => fake()->sentence(6),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $total,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the item is a product.
     */
    public function product(): static
    {
        return $this->state(function (array $attributes) {
            $product = Product::factory()->create();
            return [
                'itemable_type' => Product::class,
                'itemable_id' => $product->id,
                'type' => QuoteItemType::Product,
                'description' => $product->name,
                'unit_price' => $product->sales_price,
            ];
        });
    }

    /**
     * Indicate that the item is labor.
     */
    public function labor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuoteItemType::Labor,
            'description' => 'Arbeidsuren',
            'unit_price' => 75.00,
        ]);
    }

    /**
     * Indicate that the item is mileage.
     */
    public function mileage(): static
    {
        return $this->state(function (array $attributes) {
            $distance = fake()->numberBetween(10, 200);
            return [
                'type' => QuoteItemType::Mileage,
                'description' => "Kilometers ({$distance} km)",
                'quantity' => $distance,
                'unit_price' => 0.23,
            ];
        });
    }
}
