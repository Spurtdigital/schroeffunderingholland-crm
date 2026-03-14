<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id'            => Supplier::factory(),
            'status'                 => PurchaseOrderStatus::Draft,
            'language'               => 'nl',
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(14)->toDateString(),
            'reference'              => null,
            'notes'                  => null,
            'delivery_name'          => 'Schroeffundering Holland B.V.',
            'delivery_street'        => 'Teststraat 1',
            'delivery_postal_code'   => '3000AA',
            'delivery_city'          => 'Rotterdam',
            'delivery_country'       => 'NL',
            'subtotal'               => 0,
            'vat_amount'             => 0,
            'total'                  => 0,
        ];
    }
}
