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
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 1,
            ],
            [
                'title' => 'Geen sondering aanwezig',
                'content' => 'Er is geen sondering aanwezig.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 2,
            ],
            [
                'title' => 'Toplaag fundatie',
                'content' => 'Uitgaande van een toplaag fundatie.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 3,
            ],
            [
                'title' => 'Verlengbaar schroeffundament (variabele diepte)',
                'content' => 'Uitgaande van een verlengbaar schroeffundament van {value} meter.',
                'trigger_sku' => 'VERLENGBAAR',
                'has_variable_input' => true,
                'variable_input_label' => 'Diepte fundament',
                'variable_input_suffix' => 'meter',
                'sort_order' => 4,
            ],
            [
                'title' => 'Max. belastbaar (variabel kN)',
                'content' => 'Maximaal belastbaar tot {value} kN in een voldoende draagkrachtige laag per schroeffundament.',
                'trigger_sku' => null,
                'has_variable_input' => true,
                'variable_input_label' => 'Maximale belasting',
                'variable_input_suffix' => 'kN',
                'sort_order' => 5,
            ],
            [
                'title' => 'Eerste vaste laag (sonderingen overkant weg)',
                'content' => 'Uitgaande van een eerste vaste laag tussen de {value} meter volgens sonderingen aan de overkant van de weg.',
                'trigger_sku' => null,
                'has_variable_input' => true,
                'variable_input_label' => 'Diepte eerste vaste laag',
                'variable_input_suffix' => 'meter',
                'sort_order' => 6,
            ],
            [
                'title' => 'Standaard schroeffundament 76×1600',
                'content' => 'Uitgaande van een 76 × 1600 schroeffundament.',
                'trigger_sku' => '76X1600',
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 7,
            ],
            [
                'title' => 'Kans op zetting niet uit te sluiten',
                'content' => 'Kans op zetting niet uit te sluiten.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 8,
            ],
            [
                'title' => 'Montage tot aan maaiveld',
                'content' => 'Montage tot aan het maaiveld, anders dient de klant het gat te graven van 35 × 35 en 40 cm diep. Er kan maximaal 35 cm onder het maaiveld worden gedraaid.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 9,
            ],
            [
                'title' => 'Vergunningsvrij',
                'content' => 'Ervan uitgaande dat de constructie vergunningsvrij is.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 10,
            ],
            [
                'title' => 'Niet bekend of vergunningsvrij',
                'content' => 'Niet bekend of deze vergunningsvrij of vergunningsplichtig is.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 11,
            ],
            [
                'title' => 'Tekening aanwezig',
                'content' => 'Tekening aanwezig.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 12,
            ],
            [
                'title' => 'Geen tekening aanwezig',
                'content' => 'Er is geen tekening aanwezig.',
                'trigger_sku' => null,
                'has_variable_input' => false,
                'variable_input_label' => null,
                'variable_input_suffix' => null,
                'sort_order' => 13,
            ],
            [
                'title' => 'Belasting (variabel kN) volgens opgave klant/rapport',
                'content' => 'Uitgaande van een belasting van {value} kN volgens opgave klant of rapport.',
                'trigger_sku' => null,
                'has_variable_input' => true,
                'variable_input_label' => 'Belasting',
                'variable_input_suffix' => 'kN',
                'sort_order' => 14,
            ],
            [
                'title' => 'Totaalgewicht (variabel kg) volgens opgave klant',
                'content' => 'Totaalgewicht {value} kg volgens opgave klant.',
                'trigger_sku' => null,
                'has_variable_input' => true,
                'variable_input_label' => 'Totaalgewicht',
                'variable_input_suffix' => 'kg',
                'sort_order' => 15,
            ],
            [
                'title' => 'Klant wil een (variabele constructie) bouwen',
                'content' => 'Klant wil een {value} bouwen.',
                'trigger_sku' => null,
                'has_variable_input' => true,
                'variable_input_label' => 'Type constructie',
                'variable_input_suffix' => null,
                'sort_order' => 16,
            ],
        ];

        foreach ($assumptions as $data) {
            QuoteAssumption::updateOrCreate(
                ['title' => $data['title']],
                $data + ['active' => true]
            );
        }

        // Verwijder verouderde records die zijn hernoemd
        QuoteAssumption::where('title', 'Max. belastbaar 75 kN (verlengbaar)')->delete();
        QuoteAssumption::where('title', 'Verlengbaar schroeffundament 3.5m')->delete();
        QuoteAssumption::where('title', 'Eerste vaste laag 2-5m (sonderingen overkant weg)')->delete();

        $this->command->info('QuoteAssumptions seeded: '.count($assumptions).' aannames aangemaakt/bijgewerkt.');
    }
}
