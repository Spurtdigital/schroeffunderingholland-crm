<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 *
 * Produceert echte SFH-producten op basis van een vaste lijst.
 * Elke aanroep van ::factory()->create() pakt een willekeurig product uit de lijst
 * en maakt het aan (of haalt het op als de SKU al bestaat).
 */
class ProductFactory extends Factory
{
    /**
     * Volledige productcatalogus van Schroeffundering Holland.
     */
    private static array $catalog = [
        [
            'sku' => 'SFH-STELVOET-M24-250',
            'name' => 'Losse stelvoet met M24 draadeind 250mm',
            'short_description' => 'Verstelbare stelvoet, M24 draadeind 250mm',
            'description' => 'Losse stelvoet met M24 draadeind van 250mm lengte. Geschikt voor hoogteverstelling van schroefpaalfunderingen. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 11.50,
            'sales_price' => 19.95,
            'stock_quantity' => 150,
            'min_stock_level' => 20,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-MONTAGEHOEK',
            'name' => 'Montagehoek voor schroefpalen',
            'short_description' => 'Stalen montagehoek voor bevestiging aan paal',
            'description' => 'Gegalvaniseerde stalen montagehoek voor het bevestigen van constructiedelen aan schroefpalen. Per stuk.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 4.25,
            'sales_price' => 7.50,
            'stock_quantity' => 300,
            'min_stock_level' => 50,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-MONTAGEPLAAT-M24',
            'name' => 'Montageplaat met M24 moer',
            'short_description' => 'Stalen montageplaat inclusief M24 moer',
            'description' => 'Stalen montageplaat met gelaste M24 moer. Voor het verbinden van liggers en balken aan de schroefpaal. Gegalvaniseerd.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 5.25,
            'sales_price' => 9.25,
            'stock_quantity' => 250,
            'min_stock_level' => 40,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-MONTAGESLEUTEL',
            'name' => 'Montagesleutel voor schroefpalen',
            'short_description' => 'Speciale montagesleutel voor het draaien van schroefpalen',
            'description' => 'Professionele montagesleutel speciaal ontworpen voor het handmatig indraaien en verstellen van schroefpalen. Robuust staal, ergonomische grip.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 68.00,
            'sales_price' => 120.00,
            'stock_quantity' => 25,
            'min_stock_level' => 5,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-120CM',
            'name' => 'Schroefpaal 120cm',
            'short_description' => 'Standaard schroefpaal, lengte 120cm',
            'description' => 'Gegalvaniseerde stalen schroefpaal, lengte 120cm. Geschikt voor lichte toepassingen zoals terrasfunderingen en lichte aanbouwen.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 31.00,
            'sales_price' => 55.00,
            'stock_quantity' => 100,
            'min_stock_level' => 15,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-160CM',
            'name' => 'Schroefpaal 160cm',
            'short_description' => 'Standaard schroefpaal, lengte 160cm',
            'description' => 'Gegalvaniseerde stalen schroefpaal, lengte 160cm. Ideaal voor terrasoverkappingen, tuinhuizen en lichte funderingen op slappe grond.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 35.50,
            'sales_price' => 62.50,
            'stock_quantity' => 80,
            'min_stock_level' => 10,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-73-UBGL-1ST',
            'name' => 'Schroefpaal 73cm met U-beugel voor 7cm balk',
            'short_description' => 'Schroefpaal 73cm met U-beugel, 1 stuk',
            'description' => 'Schroefpaal 73cm inclusief U-beugel geschikt voor 7cm houten balk. Per stuk. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 15.75,
            'sales_price' => 27.75,
            'stock_quantity' => 200,
            'min_stock_level' => 30,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-73-UBGL-2ST',
            'name' => 'Schroefpaal 73cm met U-beugel voor 7cm balk — 2 stuks',
            'short_description' => 'Schroefpaal 73cm met U-beugel, voordeelset 2 stuks',
            'description' => 'Voordeelset: 2× Schroefpaal 73cm inclusief U-beugel voor 7cm houten balk. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 31.50,
            'sales_price' => 55.50,
            'stock_quantity' => 120,
            'min_stock_level' => 20,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-73-UBGL-4ST',
            'name' => 'Schroefpaal 73cm met U-beugel voor 7cm balk — 4 stuks',
            'short_description' => 'Schroefpaal 73cm met U-beugel, voordeelset 4 stuks',
            'description' => 'Voordeelset: 4× Schroefpaal 73cm inclusief U-beugel voor 7cm houten balk. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 63.00,
            'sales_price' => 110.00,
            'stock_quantity' => 80,
            'min_stock_level' => 10,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-73-UBGL-6ST',
            'name' => 'Schroefpaal 73cm met U-beugel voor 7cm balk — 6 stuks',
            'short_description' => 'Schroefpaal 73cm met U-beugel, voordeelset 6 stuks',
            'description' => 'Voordeelset: 6× Schroefpaal 73cm inclusief U-beugel voor 7cm houten balk. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 91.50,
            'sales_price' => 160.00,
            'stock_quantity' => 60,
            'min_stock_level' => 8,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-73-UBGL-8ST',
            'name' => 'Schroefpaal 73cm met U-beugel voor 7cm balk — 8 stuks',
            'short_description' => 'Schroefpaal 73cm met U-beugel, voordeelset 8 stuks',
            'description' => 'Voordeelset: 8× Schroefpaal 73cm inclusief U-beugel voor 7cm houten balk. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 111.50,
            'sales_price' => 195.00,
            'stock_quantity' => 40,
            'min_stock_level' => 5,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-80CM-2ST',
            'name' => 'Schroefpaal 80cm — 2 stuks',
            'short_description' => 'Schroefpaal 80cm, voordeelset 2 stuks',
            'description' => 'Voordeelset: 2× Schroefpaal 80cm. Gegalvaniseerd staal, geschikt voor terrasfunderingen en lichte constructies.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 47.00,
            'sales_price' => 82.50,
            'stock_quantity' => 100,
            'min_stock_level' => 15,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-80CM-4ST',
            'name' => 'Schroefpaal 80cm — 4 stuks',
            'short_description' => 'Schroefpaal 80cm, voordeelset 4 stuks',
            'description' => 'Voordeelset: 4× Schroefpaal 80cm. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 94.00,
            'sales_price' => 165.00,
            'stock_quantity' => 70,
            'min_stock_level' => 10,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-80CM-6ST',
            'name' => 'Schroefpaal 80cm — 6 stuks',
            'short_description' => 'Schroefpaal 80cm, voordeelset 6 stuks',
            'description' => 'Voordeelset: 6× Schroefpaal 80cm. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 140.00,
            'sales_price' => 245.00,
            'stock_quantity' => 50,
            'min_stock_level' => 8,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-80CM-8ST',
            'name' => 'Schroefpaal 80cm — 8 stuks',
            'short_description' => 'Schroefpaal 80cm, voordeelset 8 stuks',
            'description' => 'Voordeelset: 8× Schroefpaal 80cm. Gegalvaniseerd staal.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 183.00,
            'sales_price' => 320.00,
            'stock_quantity' => 35,
            'min_stock_level' => 5,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-STELSET-M24',
            'name' => 'Stelset compleet met M24 draadeind',
            'short_description' => 'Complete stelset inclusief M24 draadeind',
            'description' => 'Complete stelset bestaande uit stelvoet, M24 draadeind, moer en sluitring. Direct inzetbaar voor hoogteverstelling van schroefpaalfunderingen.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 14.50,
            'sales_price' => 25.50,
            'stock_quantity' => 200,
            'min_stock_level' => 30,
            'is_active' => true,
        ],
        // Extra diensten / arbeidskosten
        [
            'sku' => 'SFH-MONT-UURLOON',
            'name' => 'Montage schroefpalen (per uur)',
            'short_description' => 'Arbeidskosten montage, per uur',
            'description' => 'Uurtarief voor het plaatsen van schroefpalen door SFH-monteurs. Inclusief gebruik van aggregaat en gereedschap.',
            'supplier_name' => null,
            'purchase_price' => 0.00,
            'sales_price' => 95.00,
            'stock_quantity' => 9999,
            'min_stock_level' => 0,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-INST-FUNDERINGSPL',
            'name' => 'Funderingsonderzoek & adviesrapport',
            'short_description' => 'Grondonderzoek en funderingsadvies op locatie',
            'description' => 'Locatiebezoek door gecertificeerd funderingsadviseur, inclusief grondonderzoek en schriftelijk adviesrapport met aanbeveling.',
            'supplier_name' => null,
            'purchase_price' => 0.00,
            'sales_price' => 395.00,
            'stock_quantity' => 9999,
            'min_stock_level' => 0,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-REISKOSTEN-KM',
            'name' => 'Reiskosten (per km)',
            'short_description' => 'Reiskostenvergoeding per kilometer',
            'description' => 'Reiskostenvergoeding voor montage op locatie. Prijs per kilometer vanaf bedrijfslocatie Markelo.',
            'supplier_name' => null,
            'purchase_price' => 0.00,
            'sales_price' => 0.23,
            'stock_quantity' => 9999,
            'min_stock_level' => 0,
            'is_active' => true,
        ],
    ];

    /**
     * Geeft een willekeurig product uit de catalogus terug.
     * Wordt gebruikt door Quote- en Project-seeders voor het vullen van regelitems.
     */
    public function definition(): array
    {
        $item = fake()->randomElement(self::$catalog);

        return [
            'sku' => $item['sku'],
            'name' => $item['name'],
            'short_description' => $item['short_description'],
            'description' => $item['description'],
            'supplier_name' => $item['supplier_name'],
            'purchase_price' => $item['purchase_price'],
            'sales_price' => $item['sales_price'],
            'stock_quantity' => $item['stock_quantity'],
            'min_stock_level' => $item['min_stock_level'],
            'is_active' => $item['is_active'],
        ];
    }

    /**
     * Geef de volledige catalogus terug als array.
     * Gebruik dit in seeders om alle producten in één keer aan te maken.
     */
    public static function catalog(): array
    {
        return self::$catalog;
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $minLevel = $attributes['min_stock_level'] ?? 10;

            return [
                'stock_quantity' => fake()->numberBetween(1, $minLevel),
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
