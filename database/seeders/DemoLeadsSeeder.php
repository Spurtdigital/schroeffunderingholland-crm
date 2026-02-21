<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeder met 10 realistische aanvragen voor Schroeffundering Holland.
 *
 * - Adressen: allemaal binnen ~50 km van Rotterdam (Schroeffundering Holland)
 * - Datum: varieert van 21 februari 2026 t/m 15 maart 2026
 * - Producten: echte SFH schroefpaalproducten (SKU's uit ProductFactory::catalog())
 * - Reisafstand: hardcoded realistische waarden (Google Maps)
 * - Status: mix van Nieuw, Contacted, In behandeling
 *
 * Gebruik:
 *   php artisan db:seed --class=DemoLeadsSeeder
 *
 * Vereiste: DatabaseSeeder moet eerst gedraaid zijn zodat de producten bestaan.
 */
class DemoLeadsSeeder extends Seeder
{
    /**
     * 10 realistische aanvragen rondom Rotterdam.
     * Afstanden zijn reistijd-gebaseerde schattingen vanaf Rotterdam Centrum.
     *
     * SKU-mapping (oud → nieuw):
     *   SFH-SPAAL-089  → SFH-SPAAL-120CM  (lichte paal, kortste variant)
     *   SFH-SPAAL-114  → SFH-SPAAL-160CM  (middelzware paal)
     *   SFH-SPAAL-168  → SFH-SPAAL-160CM  (geen zwaardere variant; 160cm is het maximum)
     *   SFH-KOPPEL-89  → SFH-STELSET-M24  (stelset vervangt koppelstuk logica)
     *   SFH-KOPPEL-114 → SFH-STELSET-M24
     *   SFH-KOPPL-UNIV → SFH-MONTAGEPLAAT-M24
     */
    private array $leads = [
        [
            'company_name' => 'Bouwbedrijf Van der Berg B.V.',
            'company_email' => 'info@vandenberg-bouw.nl',
            'company_phone' => '010-4521890',
            'street' => 'Westzeedijk',
            'house_number' => '384',
            'postal_code' => '3024 EK',
            'city' => 'Rotterdam',
            'contact_name' => 'Pieter van den Berg',
            'contact_email' => 'kevin@spurt.digital',
            'contact_phone' => '06-12345678',
            'message' => 'Wij zijn bezig met de fundering van een nieuwe aanbouw (45 m²) op een woning uit 1930. De bodem is slap en de huidige fundering is onvoldoende. Graag een advies en offerte voor schroefpalen.',
            'distance_km' => 4,
            'duration_text' => '9 min',
            'products' => ['SFH-SPAAL-120CM', 'SFH-STELSET-M24', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON'],
            'status' => LeadStatus::New,
            'created_at' => '2026-02-21 09:14:00',
        ],
        [
            'company_name' => 'Architectenbureau Klaassen & Partners',
            'company_email' => 'info@klaassen-architecten.nl',
            'company_phone' => '010-7831200',
            'street' => 'Bergselaan',
            'house_number' => '72',
            'postal_code' => '3037 EL',
            'city' => 'Rotterdam',
            'contact_name' => 'Sandra Klaassen',
            'contact_email' => 'bob@schroeffunderingholland.nl',
            'contact_phone' => '06-23456789',
            'message' => 'Namens een particuliere opdrachtgever zoeken wij een funderings oplossing voor een tuinkamer (30 m²) in Rotterdam-Noord. Grond is veenachtig, draagkracht onvoldoende voor traditionele strip fundering. Interesse in schroefpaalfundering.',
            'distance_km' => 7,
            'duration_text' => '14 min',
            'products' => ['SFH-SPAAL-120CM', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON'],
            'status' => LeadStatus::New,
            'created_at' => '2026-02-22 11:30:00',
        ],
        [
            'company_name' => 'Aannemingsbedrijf De Groot Schiedam',
            'company_email' => 'office@degroot-aanneming.nl',
            'company_phone' => '010-2461500',
            'street' => 'Nieuwe Damlaan',
            'house_number' => '18',
            'postal_code' => '3117 ZP',
            'city' => 'Schiedam',
            'contact_name' => 'Mark de Groot',
            'contact_email' => 'kevin@spurt.digital',
            'contact_phone' => '06-34567890',
            'message' => 'Wij hebben een opdracht voor renovatie en uitbreiding van een bedrijfspand in Schiedam. Het nieuwe gedeelte (80 m²) dient op schroefpalen gefundeerd te worden. We zoeken een gespecialiseerde partij voor het plaatsen van langere palen.',
            'distance_km' => 11,
            'duration_text' => '18 min',
            'products' => ['SFH-SPAAL-160CM', 'SFH-STELSET-M24', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON'],
            'status' => LeadStatus::Contacted,
            'created_at' => '2026-02-24 08:45:00',
        ],
        [
            'company_name' => 'Woningcorporatie Vestia Delft',
            'company_email' => 'technisch@vestia-delft.nl',
            'company_phone' => '015-2136700',
            'street' => 'Phoenixstraat',
            'house_number' => '16',
            'postal_code' => '2611 AL',
            'city' => 'Delft',
            'contact_name' => 'Inge Verhoeven',
            'contact_email' => 'bob@schroeffunderingholland.nl',
            'contact_phone' => '06-45678901',
            'message' => 'We onderhouden een woningblok in Delft-Centrum dat funderingsproblemen vertoont. Na inspectie is herstel door middel van schroefpalen geadviseerd. Het gaat om 6 woningen met een totaal paalwerk van ca. 120 palen. Graag een gedetailleerde offerte.',
            'distance_km' => 18,
            'duration_text' => '24 min',
            'products' => ['SFH-SPAAL-160CM', 'SFH-STELSET-M24', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
            'status' => LeadStatus::Contacted,
            'created_at' => '2026-02-25 14:20:00',
        ],
        [
            'company_name' => 'Particulier — Dhr. F. Janssen',
            'company_email' => 'f.janssen@gmail.com',
            'company_phone' => '06-56789012',
            'street' => 'Kerkstraat',
            'house_number' => '43',
            'postal_code' => '2981 AK',
            'city' => 'Ridderkerk',
            'contact_name' => 'Frank Janssen',
            'contact_email' => 'kevin@spurt.digital',
            'contact_phone' => '06-56789012',
            'message' => 'Mijn jaren-30 woning heeft last van verzakking aan de achterkant. Na advies van een bouwkundige wordt schroefpaalfundering aanbevolen voor de achtergevel (ca. 8 palen). Graag meer informatie en een vrijblijvende offerte.',
            'distance_km' => 14,
            'duration_text' => '20 min',
            'products' => ['SFH-SPAAL-120CM', 'SFH-STELSET-M24', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON'],
            'status' => LeadStatus::New,
            'created_at' => '2026-02-27 16:05:00',
        ],
        [
            'company_name' => 'Projectontwikkeling Havenzicht B.V.',
            'company_email' => 'projecten@havenzicht.nl',
            'company_phone' => '0182-651800',
            'street' => 'Havenstraat',
            'house_number' => '210',
            'postal_code' => '2801 PH',
            'city' => 'Gouda',
            'contact_name' => 'Erik Boonstra',
            'contact_email' => 'bob@schroeffunderingholland.nl',
            'contact_phone' => '06-67890123',
            'message' => 'Wij ontwikkelen een kleinschalig woonproject (12 woningen) in Gouda op een voormalig industrieterrein. De bodem bestaat deels uit stortgrond en veen. We onderzoeken de mogelijkheid van schroefpaalfundering als alternatief voor heipalen. Graag een oriënterend gesprek en indicatieofferte.',
            'distance_km' => 32,
            'duration_text' => '38 min',
            'products' => ['SFH-SPAAL-160CM', 'SFH-STELSET-M24', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
            'status' => LeadStatus::New,
            'created_at' => '2026-03-01 10:00:00',
        ],
        [
            'company_name' => 'Aannemer Gebr. Verhoef',
            'company_email' => 'info@gebr-verhoef.nl',
            'company_phone' => '015-3690100',
            'street' => 'Papsouwselaan',
            'house_number' => '61',
            'postal_code' => '2629 JE',
            'city' => 'Delft',
            'contact_name' => 'Kees Verhoef',
            'contact_email' => 'kevin@spurt.digital',
            'contact_phone' => '06-78901234',
            'message' => 'We plaatsen een houten terrasoverkapping op een bestaand terras voor een particuliere klant. Het terras heeft slechte draagkracht. We willen 6 schroefpalen plaatsen als fundering voor de staanders. Graag advies over het juiste type en diepte.',
            'distance_km' => 20,
            'duration_text' => '26 min',
            'products' => ['SFH-SPAAL-120CM', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON'],
            'status' => LeadStatus::New,
            'created_at' => '2026-03-03 09:30:00',
        ],
        [
            'company_name' => 'Sloopbedrijf & Bouw Hendriksen',
            'company_email' => 'projectleider@hendriksen-bouw.nl',
            'company_phone' => '010-4990055',
            'street' => 'Mozartlaan',
            'house_number' => '5',
            'postal_code' => '2719 EK',
            'city' => 'Zoetermeer',
            'contact_name' => 'Rob Hendriksen',
            'contact_email' => 'bob@schroeffunderingholland.nl',
            'contact_phone' => '06-89012345',
            'message' => 'Na sloop van een oude schuur op een bedrijfsperceel willen we een nieuwe opslaghal bouwen (120 m²). Grondonderzoek heeft uitgewezen dat de bodemopbouw slappe veenlagen bevat tot 6 meter diepte. Schroefpaalfundering met langere palen is geadviseerd.',
            'distance_km' => 35,
            'duration_text' => '42 min',
            'products' => ['SFH-SPAAL-160CM', 'SFH-STELSET-M24', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
            'status' => LeadStatus::Contacted,
            'created_at' => '2026-03-05 13:15:00',
        ],
        [
            'company_name' => 'Particulier — Mevr. A. de Boer',
            'company_email' => 'a.deboer@hotmail.com',
            'company_phone' => '06-90123456',
            'street' => 'Koninginnelaan',
            'house_number' => '12',
            'postal_code' => '2712 HT',
            'city' => 'Zoetermeer',
            'contact_name' => 'Annemieke de Boer',
            'contact_email' => 'kevin@spurt.digital',
            'contact_phone' => '06-90123456',
            'message' => 'Ik wil een uitbouw realiseren aan de achterzijde van mijn woning (ca. 18 m²). De grond in onze tuin is erg slap; de buurman heeft recent hetzelfde gedaan met schroefpalen en was erg tevreden. Graag een offerte.',
            'distance_km' => 33,
            'duration_text' => '39 min',
            'products' => ['SFH-SPAAL-120CM', 'SFH-STELSET-M24', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON'],
            'status' => LeadStatus::New,
            'created_at' => '2026-03-08 10:50:00',
        ],
        [
            'company_name' => 'Vastgoed & Beheer Dordrecht B.V.',
            'company_email' => 'vastgoed@vbdordrecht.nl',
            'company_phone' => '078-6140200',
            'street' => 'Vriesestraat',
            'house_number' => '130',
            'postal_code' => '3311 SP',
            'city' => 'Dordrecht',
            'contact_name' => 'Joost Vlaming',
            'contact_email' => 'bob@schroeffunderingholland.nl',
            'contact_phone' => '06-01234567',
            'message' => 'Wij bezitten een rijwoning in Dordrecht-Centrum die ernstige funderingsproblemen heeft door houtrot in de houten palen. We zijn op zoek naar een oplossing met schroefpaalopdruk. Het gaat om een tussenwoning (bj. 1920) met 3 lagen. Graag een funderingsonderzoek en adviesrapport als eerste stap.',
            'distance_km' => 28,
            'duration_text' => '33 min',
            'products' => ['SFH-SPAAL-160CM', 'SFH-STELSET-M24', 'SFH-MONTAGEPLAAT-M24', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
            'status' => LeadStatus::New,
            'created_at' => '2026-03-15 14:00:00',
        ],
    ];

    public function run(): void
    {
        $this->command->info('Seeding 10 demo leads (SFH, feb–mrt 2026)...');

        // Haal alle benodigde producten op uit de database (aangemaakt door DatabaseSeeder)
        $allSkus = collect($this->leads)
            ->pluck('products')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        $productModels = Product::whereIn('sku', $allSkus)
            ->get()
            ->keyBy('sku');

        // Waarschuw als een SKU ontbreekt
        foreach ($allSkus as $sku) {
            if (! $productModels->has($sku)) {
                $this->command->warn("  Product met SKU '{$sku}' niet gevonden — run DatabaseSeeder eerst.");
            }
        }

        // Maak leads aan
        foreach ($this->leads as $leadData) {
            // Bedrijf aanmaken of ophalen op naam
            $company = Company::firstOrCreate(
                ['name' => $leadData['company_name']],
                [
                    'email' => $leadData['company_email'],
                    'phone' => $leadData['company_phone'],
                    'street' => $leadData['street'],
                    'house_number' => $leadData['house_number'],
                    'postal_code' => $leadData['postal_code'],
                    'city' => $leadData['city'],
                ]
            );

            // Stel request_data samen met bericht, gevraagde producten en afstandsinfo
            $productNames = collect($leadData['products'])
                ->map(fn ($sku) => $productModels->get($sku)?->name ?? $sku)
                ->implode(', ');

            $requestData = [
                'source' => 'website_contactformulier',
                'form_id' => 1,
                'message' => $leadData['message'],
                'requested_products' => $leadData['products'],
                'distance_km' => $leadData['distance_km'],
                'duration_text' => $leadData['duration_text'],
                'distance_origin' => 'Rotterdam, Achterdijk 1, 3068 AC Rotterdam',
            ];

            $lead = Lead::create([
                'company_id' => $company->id,
                'name' => $leadData['contact_name'],
                'email' => $leadData['contact_email'],
                'phone' => $leadData['contact_phone'],
                'street' => $leadData['street'],
                'house_number' => $leadData['house_number'],
                'postal_code' => $leadData['postal_code'],
                'city' => $leadData['city'],
                'request_data' => $requestData,
                'status' => $leadData['status'],
                'notes' => "Gevraagde producten: {$productNames}\nAfstand: {$leadData['distance_km']} km ({$leadData['duration_text']})",
                'created_at' => Carbon::parse($leadData['created_at']),
                'updated_at' => Carbon::parse($leadData['created_at']),
            ]);

            $this->command->line("  lead #{$lead->id}: {$lead->name} ({$company->name}) — {$leadData['city']} — {$leadData['distance_km']} km");
        }

        $this->command->info('Klaar! 10 leads aangemaakt.');
    }
}
