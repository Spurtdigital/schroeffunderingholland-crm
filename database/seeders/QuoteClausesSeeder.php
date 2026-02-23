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
        // --- Bullet-clausules (voorwaarden-lijst) ---
        $bullets = [
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

        foreach ($bullets as $clause) {
            QuoteClause::firstOrCreate(
                ['title' => $clause['title']],
                array_merge($clause, ['active' => true, 'type' => 'bullet']),
            );
        }

        // --- Sectie-clausules (vrije alinea's met eigen kopje) ---
        $sections = [
            [
                'title' => 'Extra informatie',
                'content' => 'Indien de werkzaamheden door Schroeffundering Holland B.V. binnen 15 meter vanaf de gevellijn worden uitgevoerd sluiten wij iedere vorm aansprakelijkheid voor funderingsschade ontstaan binnen een afstand van 15 meter van de gevellijn van het pand en naburige panden ontstaan tijdens na verrichte werkzaamheden volledig uit. De opdrachtgever is verplicht, althans wordt dringend geadviseerd, om dit risico op schade zelf af te dekken met een daarvoor bestemde verzekering. Indien er geen sondering aanwezig is, raden wij u aan deze te laten uitvoeren en met ons te delen. Indien er geen sondering is of niet zal worden uitgevoerd door de opdrachtgever, dan komt dit risico voor rekening van de opdrachtgever, Schroeffundering Holland B.V. kan hiervoor niet aansprakelijk worden gesteld.',
                'sort_order' => 110,
            ],
            [
                'title' => 'Weersvoorspelling',
                'content' => 'Indien de weersvoorspellingen te slecht zijn en het ons niet toelaat om de werkzaamheden uit te voeren, dan zullen wij de opdracht moeten verplaatsen. Houdt hierbij dus rekening mee in uw eigen planning.',
                'sort_order' => 120,
            ],
            [
                'title' => 'Klic melding',
                'content' => 'Schroeffundering Holland B.V. vraagt voor aanvang van de montage een klic melding aan bij het Kadaster voor het inzien van eventueel lopende kabels en leidingen. Blijkt dat er een EV op de klic melding zit en de werkzaamheden eerst gecontroleerd dienen te worden door de netbeheerder, dan kan dit vertraging in de startdatum opleveren. Indien het blijkt dat er kabels en leidingen liggen in het werkgebied dan dienen deze zichtbaar te worden gemaakt, dit dient door de klant vooraf te worden uitgevoerd. Als de montage door de aanwezigheid van kabels en leidingen of overige eisen niet mogelijk blijkt te zijn dan worden de kosten van de klic melding a €55,- excl. btw altijd in rekening gebracht.',
                'sort_order' => 130,
            ],
            [
                'title' => 'Wachttijd en vergoeding',
                'content' => 'Wachttijd door het niet hebben voldaan aan de gestelde voorbereidingen wordt doorbelast a €60,- per monteur excl. btw. Indien blijkt dat de voorbereidingen niet getroffen zijn en de montage om welke reden dan ook niet kan worden uitgevoerd op dezelfde dag en Schroeffundering Holland hiervoor terug moet komen wordt doorbelast bij de klant a €400,- excl. btw per gepland dagdeel.',
                'sort_order' => 140,
            ],
        ];

        foreach ($sections as $clause) {
            QuoteClause::firstOrCreate(
                ['title' => $clause['title']],
                array_merge($clause, ['active' => true, 'type' => 'section']),
            );
        }
    }
}
