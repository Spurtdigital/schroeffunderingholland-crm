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
 * - Producten: echte SFH schroefpaalproducten
 * - Reisafstand: hardcoded realistische waarden (Google Maps)
 * - Status: mix van Nieuw, Contacted, In behandeling
 *
 * Gebruik:
 *   php artisan db:seed --class=DemoLeadsSeeder
 */
class DemoLeadsSeeder extends Seeder
{
    /**
     * Echte SFH-productnamen en SKU's die aangemaakt worden
     * als ze nog niet bestaan.
     */
    private array $sfhProducts = [
        [
            'sku' => 'SFH-SPAAL-089',
            'name' => 'Schroefpaal ø89mm — L=4m',
            'short_description' => 'Stalen schroefpaal ø89mm, lengte 4 meter',
            'description' => 'Gegalvaniseerde stalen schroefpaal met ø89mm buis, geschikt voor lichte tot middelzware funderingen. Lengte 4 meter.',
            'supplier_name' => 'Krinner GmbH',
            'purchase_price' => 68.00,
            'sales_price' => 112.00,
            'stock_quantity' => 80,
            'min_stock_level' => 10,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-114',
            'name' => 'Schroefpaal ø114mm — L=6m',
            'short_description' => 'Stalen schroefpaal ø114mm, lengte 6 meter',
            'description' => 'Zware schroefpaal ø114mm buis, 6 meter lang. Geschikt voor middelzware tot zware funderingen op slappe grond.',
            'supplier_name' => 'Krinner GmbH',
            'purchase_price' => 145.00,
            'sales_price' => 238.00,
            'stock_quantity' => 40,
            'min_stock_level' => 5,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-SPAAL-168',
            'name' => 'Schroefpaal ø168mm — L=8m',
            'short_description' => 'Zware schroefpaal ø168mm, lengte 8 meter',
            'description' => 'Extra zware schroefpaal ø168mm buis, 8 meter lang. Voor zware belastingen en diepe funderingen.',
            'supplier_name' => 'Krinner GmbH',
            'purchase_price' => 285.00,
            'sales_price' => 465.00,
            'stock_quantity' => 20,
            'min_stock_level' => 3,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-KOPPEL-89',
            'name' => 'Koppelstuk ø89mm (verlengstuk)',
            'short_description' => 'Verlengkoppeling voor ø89mm paal',
            'description' => 'Stalen koppelstuk voor het verlengen van ø89mm schroefpalen. Gegalvaniseerd.',
            'supplier_name' => 'Krinner GmbH',
            'purchase_price' => 18.50,
            'sales_price' => 32.00,
            'stock_quantity' => 120,
            'min_stock_level' => 20,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-KOPPEL-114',
            'name' => 'Koppelstuk ø114mm (verlengstuk)',
            'short_description' => 'Verlengkoppeling voor ø114mm paal',
            'description' => 'Stalen koppelstuk voor het verlengen van ø114mm schroefpalen. Gegalvaniseerd.',
            'supplier_name' => 'Krinner GmbH',
            'purchase_price' => 26.00,
            'sales_price' => 44.00,
            'stock_quantity' => 80,
            'min_stock_level' => 10,
            'is_active' => true,
        ],
        [
            'sku' => 'SFH-KOPPL-UNIV',
            'name' => 'Verstelbare koplaatbeugel (universeel)',
            'short_description' => 'Verstelbare koplaatbeugel voor liggers en balken',
            'description' => 'Universele verstelbare koplaatbeugel, geschikt voor houten liggers 90–200mm. Incl. montagesets.',
            'supplier_name' => 'SFH Eigen productie',
            'purchase_price' => 22.00,
            'sales_price' => 39.50,
            'stock_quantity' => 200,
            'min_stock_level' => 25,
            'is_active' => true,
        ],
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
    ];

    /**
     * 10 realistische aanvragen rondom Rotterdam.
     * Afstanden zijn reistijd-gebaseerde schattingen vanaf Rotterdam Centrum.
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
            'products' => ['SFH-SPAAL-089', 'SFH-KOPPEL-89', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON'],
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
            'products' => ['SFH-SPAAL-089', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON'],
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
            'message' => 'Wij hebben een opdracht voor renovatie en uitbreiding van een bedrijfspand in Schiedam. Het nieuwe gedeelte (80 m²) dient op schroefpalen gefundeerd te worden. We zoeken een gespecialiseerde partij voor het plaatsen van ø114mm palen.',
            'distance_km' => 11,
            'duration_text' => '18 min',
            'products' => ['SFH-SPAAL-114', 'SFH-KOPPEL-114', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON'],
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
            'products' => ['SFH-SPAAL-114', 'SFH-KOPPEL-114', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
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
            'products' => ['SFH-SPAAL-089', 'SFH-KOPPEL-89', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON'],
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
            'products' => ['SFH-SPAAL-168', 'SFH-KOPPEL-114', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
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
            'products' => ['SFH-SPAAL-089', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON'],
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
            'message' => 'Na sloop van een oude schuur op een bedrijfsperceel willen we een nieuwe opslaghal bouwen (120 m²). Grondonderzoek heeft uitgewezen dat de bodemopbouw slappe veenlagen bevat tot 6 meter diepte. Schroefpaalfundering met ø168mm palen is geadviseerd.',
            'distance_km' => 35,
            'duration_text' => '42 min',
            'products' => ['SFH-SPAAL-168', 'SFH-KOPPEL-114', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
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
            'products' => ['SFH-SPAAL-089', 'SFH-KOPPEL-89', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON'],
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
            'products' => ['SFH-SPAAL-114', 'SFH-KOPPEL-114', 'SFH-KOPPL-UNIV', 'SFH-MONT-UURLOON', 'SFH-INST-FUNDERINGSPL'],
            'status' => LeadStatus::New,
            'created_at' => '2026-03-15 14:00:00',
        ],
    ];

    public function run(): void
    {
        $this->command->info('Seeding 10 demo leads (SFH, feb–mrt 2026)...');

        // Zorg dat alle SFH-producten bestaan (updateOrCreate op SKU)
        $productModels = [];
        foreach ($this->sfhProducts as $productData) {
            $product = Product::updateOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
            $productModels[$productData['sku']] = $product;
            $this->command->line("  product: {$product->sku} — {$product->name}");
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
                ->map(fn ($sku) => $productModels[$sku]?->name ?? $sku)
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
