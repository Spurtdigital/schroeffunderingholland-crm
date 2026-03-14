<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'         => fake()->company(),
            'contact_name' => fake()->name(),
            'email'        => fake()->companyEmail(),
            'phone'        => fake()->phoneNumber(),
            'website'      => fake()->url(),
            'notes'        => null,
            'is_active'    => true,
            'language'     => 'nl',
            'address'      => fake()->streetAddress(),
            'postal_code'  => fake()->postcode(),
            'city'         => fake()->city(),
            'country'      => 'NL',
        ];
    }
}
