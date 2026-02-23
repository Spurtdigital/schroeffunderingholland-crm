<?php

namespace Database\Seeders;

use App\Models\QuoteClause;
use Illuminate\Database\Seeder;

class QuoteClausesSeeder extends Seeder
{
    /**
     * Seed de standaard clausules voor de sluitingstekst van offertes.
     * Deze komen uit de originele sluitingstekst van Schroeffundering Holland B.V.
     */
    public function run(): void
    {
        $clauses = [
            [
                'title' => 'Stroom 230V aanwezig',
                'content' => 'Er op locatie een 230 Volt stroomaansluiting aanwezig is voor onze machines.',
                'sort_order' => 10,
            ],
            [
                'title' => 'Locatie obstakelvrij',
                'content' => 'De locatie volledig vrij is van obstakels zoals puin, wortels, stenen, kabels en leidingen. Opgraven van puin, wortels, stenen, kabels en leidingen wordt doorbelast a €60,- per uur excl. btw.',
                'sort_order' => 20,
            ],
            [
                'title' => 'Bereikbaarheid montagemachines',
                'content' => 'De montage locatie goed bereikbaar is voor de aangeboden montagemachines, zie afmetingen en specificaties in de offerte.',
                'sort_order' => 30,
            ],
            [
                'title' => 'Graven door klant (onder maaiveld)',
                'content' => 'De werkzaamheden heden voor het onder maaiveld draaien op locatie door de klant zelf worden uitgevoerd. Het gat dient te zijn gegraven, maximaal 35 x 35 cm en 40 cm diep.',
                'sort_order' => 40,
            ],
            [
                'title' => 'Afwijking X-Y as',
                'content' => 'Er rekening gehouden dient te worden met een mogelijke afwijking (3-4 cm) in de X-Y as van het schroeffundament.',
                'sort_order' => 50,
            ],
            [
                'title' => 'Montageposities uitgezet door klant',
                'content' => 'De montage posities in maatvoering en hoogte maten door de klant zijn uitgezet en/of aangegeven middels een piketpaaltje.',
                'sort_order' => 60,
            ],
            [
                'title' => 'Parkeren geregeld door klant',
                'content' => 'Er voldoende parkeergelegenheid en eventueel een parkeervergunning aanwezig is, dit door de klant te voorzien. Parkeren dient op 20 meter afstand van de montage locatie te zijn.',
                'sort_order' => 70,
            ],
        ];

        foreach ($clauses as $clause) {
            QuoteClause::firstOrCreate(
                ['title' => $clause['title']],
                array_merge($clause, ['active' => true]),
            );
        }
    }
}
