<?php

namespace Database\Seeders;

use App\Models\QuoteAssumption;
use Illuminate\Database\Seeder;

class QuoteAssumptionsSeeder extends Seeder
{
    public function run(): void
    {
        $assumptions = [
            [
                'title' => 'Zoals door klant aangevraagd',
                'content' => 'Zoals door de klant aangevraagd.',
                'trigger_sku' => null,
                'sort_order' => 1,
            ],
            [
                'title' => 'Geen sondering aanwezig',
                'content' => 'Er is geen sondering aanwezig.',
                'trigger_sku' => null,
                'sort_order' => 2,
            ],
            [
                'title' => 'Toplaag fundatie',
                'content' => 'Uitgaande van een toplaag fundatie.',
                'trigger_sku' => null,
                'sort_order' => 3,
            ],
            [
                'title' => 'Verlengbaar schroeffundament 3.5m',
                'content' => 'Uitgaande van een verlengbaar schroeffundament van 3,5 meter. Montage tot in de eerste zandlaag.',
                'trigger_sku' => 'VERLENGBAAR',
                'sort_order' => 4,
            ],
            [
                'title' => 'Max. belastbaar 75 kN (verlengbaar)',
                'content' => 'Maximaal belastbaar tot 75 kN in een voldoende draagkrachtige laag per schroeffundament, bij voldoende indraaimoment en op basis van referentie testen.',
                'trigger_sku' => 'VERLENGBAAR',
                'sort_order' => 5,
            ],
            [
                'title' => 'Eerste vaste laag 2-5m (sonderingen overkant weg)',
                'content' => 'Uitgaande van een eerste vaste laag tussen de 2-5 meter volgens sonderingen aan de overkant van de weg.',
                'trigger_sku' => null,
                'sort_order' => 6,
            ],
            [
                'title' => 'Standaard schroeffundament 76×1600',
                'content' => 'Uitgaande van een 76 × 1600 schroeffundament.',
                'trigger_sku' => '76X1600',
                'sort_order' => 7,
            ],
            [
                'title' => 'Kans op zetting niet uit te sluiten',
                'content' => 'Kans op zetting niet uit te sluiten.',
                'trigger_sku' => null,
                'sort_order' => 8,
            ],
            [
                'title' => 'Montage tot aan maaiveld',
                'content' => 'Montage tot aan het maaiveld, anders dient de klant het gat te graven van 35 × 35 en 40 cm diep. Er kan maximaal 35 cm onder het maaiveld worden gedraaid.',
                'trigger_sku' => null,
                'sort_order' => 9,
            ],
            [
                'title' => 'Vergunningsvrij',
                'content' => 'Ervan uitgaande dat de constructie vergunningsvrij is.',
                'trigger_sku' => null,
                'sort_order' => 10,
            ],
            [
                'title' => 'Niet bekend of vergunningsvrij',
                'content' => 'Niet bekend of deze vergunningsvrij of vergunningsplichtig is.',
                'trigger_sku' => null,
                'sort_order' => 11,
            ],
            [
                'title' => 'Tekening aanwezig',
                'content' => 'Tekening aanwezig.',
                'trigger_sku' => null,
                'sort_order' => 12,
            ],
            [
                'title' => 'Geen tekening aanwezig',
                'content' => 'Er is geen tekening aanwezig.',
                'trigger_sku' => null,
                'sort_order' => 13,
            ],
        ];

        foreach ($assumptions as $data) {
            QuoteAssumption::updateOrCreate(
                ['title' => $data['title']],
                $data + ['active' => true]
            );
        }

        $this->command->info('QuoteAssumptions seeded: '.count($assumptions).' aannames aangemaakt.');
    }
}
