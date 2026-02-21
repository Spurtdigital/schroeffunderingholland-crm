<?php

namespace Database\Factories;

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectItem>
 */
class ProjectItemFactory extends Factory
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

        return [
            'project_id' => Project::factory(),
            'itemable_type' => null,
            'itemable_id' => null,
            'type' => QuoteItemType::FreeLine,
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => round($quantity * $unitPrice, 2),
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
                'total' => round(($attributes['quantity'] ?? 1) * $product->sales_price, 2),
            ];
        });
    }
}
