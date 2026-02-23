<?php

namespace Database\Seeders;

use App\Models\PaymentTerm;
use Illuminate\Database\Seeder;

class PaymentTermsSeeder extends Seeder
{
    /**
     * Seed de standaard betalingsvoorwaarden voor offertes.
     */
    public function run(): void
    {
        $terms = [
            [
                'title' => '50% aanbetaling, 50% na oplevering',
                'content' => '50% aanbetaling voorafgaand aan de werkzaamheden, 50% na oplevering binnen 14 dagen na factuurdatum.',
                'is_default' => true,
                'sort_order' => 10,
            ],
            [
                'title' => '100% vooraf',
                'content' => 'Betaling 100% vooraf vóór aanvang van de werkzaamheden.',
                'is_default' => false,
                'sort_order' => 20,
            ],
            [
                'title' => '30 dagen na factuurdatum',
                'content' => 'Betaling binnen 30 dagen na factuurdatum.',
                'is_default' => false,
                'sort_order' => 30,
            ],
        ];

        foreach ($terms as $term) {
            PaymentTerm::firstOrCreate(
                ['title' => $term['title']],
                array_merge($term, ['active' => true]),
            );
        }
    }
}
