<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'converted_to_quote_id' => null,
            'name' => fake()->name(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'postal_code' => fake()->postcode(),
            'house_number' => fake()->buildingNumber(),
            'city' => fake()->city(),
            'street' => fake()->streetName(),
            'request_data' => [
                'source' => 'contact_form_7',
                'form_id' => fake()->numberBetween(1, 5),
                'message' => fake()->sentence(20),
            ],
            'status' => LeadStatus::New,
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the lead is contacted.
     */
    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::Contacted,
        ]);
    }

    /**
     * Indicate that the lead has been converted to a quote.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::Converted,
            'converted_to_quote_id' => \App\Models\Quote::factory(),
        ]);
    }

    /**
     * Indicate that the lead has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::Rejected,
        ]);
    }
}
