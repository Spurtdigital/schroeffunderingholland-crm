<?php

namespace App\Console\Commands;

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SeedTestData extends Command
{
    protected $signature = 'app:seed-test-data';

    protected $description = 'Voeg testdata toe: gebruiker kevin@spurt.digital, leveranciers, producten, leads.';

    public function handle(): int
    {
        $this->info('==> Gebruiker kevin@spurt.digital aanmaken...');
        $this->createUser();

        $this->info('==> Leveranciers aanmaken...');
        [$supplierNL, $supplierDE] = $this->createSuppliers();

        $this->info('==> Producten koppelen aan leveranciers...');
        $this->linkProducts($supplierNL, $supplierDE);

        $this->info('==> Test-leads aanmaken...');
        $this->createLeads();

        $this->newLine();
        $this->info('Klaar! Testdata aangemaakt.');

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function createUser(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::updateOrCreate(
            ['email' => 'kevin@spurt.digital'],
            [
                'name'              => 'Kevin (Spurt Digital)',
                'password'          => Hash::make('95Kevin03!'),
                'email_verified_at' => Carbon::now(),
            ]
        );

        $user->syncRoles([$role]);

        $this->line("  Gebruiker: kevin@spurt.digital (super_admin)");
    }

    private function createSuppliers(): array
    {
        $supplierNL = Supplier::firstOrCreate(
            ['name' => 'Van Aarsen Staalhandel'],
            [
                'contact_name' => 'Henk van Aarsen',
                'email'        => 'info@vanaarsen-staal.nl',
                'phone'        => '0547-261800',
                'website'      => 'https://www.vanaarsen-staal.nl',
                'language'     => 'nl',
                'address'      => 'Industrieweg 14',
                'postal_code'  => '7475 AH',
                'city'         => 'Markelo',
                'country'      => 'NL',
                'is_active'    => true,
                'notes'        => 'Vaste leverancier schroefpalen en staalonderdelen.',
            ]
        );
        $this->line("  NL-leverancier: {$supplierNL->name}");

        $supplierDE = Supplier::firstOrCreate(
            ['name' => 'Krinner GmbH'],
            [
                'contact_name' => 'Hans Müller',
                'email'        => 'vertrieb@krinner.de',
                'phone'        => '+49 8724 9680',
                'website'      => 'https://www.krinner.de',
                'language'     => 'de',
                'address'      => 'Industriestraße 12',
                'postal_code'  => '84359',
                'city'         => 'Simbach am Inn',
                'country'      => 'DE',
                'is_active'    => true,
                'notes'        => 'Duitse fabrikant — buitenlandse inkoop, 0% BTW.',
            ]
        );
        $this->line("  DE-leverancier: {$supplierDE->name}");

        return [$supplierNL, $supplierDE];
    }

    private function linkProducts(Supplier $supplierNL, Supplier $supplierDE): void
    {
        // Koppel de eerste helft van de producten aan de NL leverancier
        $products = Product::all();

        $half = (int) ceil($products->count() / 2);

        $products->take($half)->each(function (Product $p) use ($supplierNL) {
            if ($p->supplier_id === null) {
                $p->update(['supplier_id' => $supplierNL->id]);
                $this->line("  → {$p->name} → {$supplierNL->name}");
            }
        });

        $products->skip($half)->each(function (Product $p) use ($supplierDE) {
            if ($p->supplier_id === null) {
                $p->update(['supplier_id' => $supplierDE->id]);
                $this->line("  → {$p->name} → {$supplierDE->name}");
            }
        });
    }

    private function createLeads(): void
    {
        $leads = [
            [
                'company'     => 'Bouwbedrijf De Rooij BV',
                'name'        => 'Marc de Rooij',
                'email'       => 'marc@derooijbouw.nl',
                'phone'       => '0612345678',
                'street'      => 'Nieuwstraat',
                'house_number'=> '12',
                'postal_code' => '5038 SC',
                'city'        => 'Tilburg',
                'status'      => LeadStatus::New,
                'notes'       => "Fundering terrasuitbouw, circa 20m². Grond lijkt zacht.",
                'request_data'=> [
                    'naam'          => 'Marc de Rooij',
                    'bedrijfsnaam'  => 'Bouwbedrijf De Rooij BV',
                    'emailadres'    => 'marc@derooijbouw.nl',
                    'telefoon'      => '0612345678',
                    'straatnaam'    => 'Nieuwstraat 12',
                    'postcode'      => '5038 SC',
                    'plaats'        => 'Tilburg',
                    'omschrijving'  => 'Fundering terrasuitbouw, circa 20m². Grond lijkt zacht.',
                    'gewicht'       => '3500 kg',
                    'hoogte'        => '2800 mm',
                    'form_title'    => 'Maatwerk offerte aanvraag',
                    'submitted_at'  => Carbon::now()->subDays(2)->toDateTimeString(),
                ],
            ],
            [
                'company'     => 'Aannemer Visser & Zonen',
                'name'        => 'Peter Visser',
                'email'       => 'peter@visseraannemer.nl',
                'phone'       => '0687654321',
                'street'      => 'Hoofdweg',
                'house_number'=> '88B',
                'postal_code' => '9765 AA',
                'city'        => 'Groningen',
                'status'      => LeadStatus::Contacted,
                'notes'       => "Vervanging houten paalfundering. Woning uit 1925, ernstige verzakking.",
                'request_data'=> [
                    'naam'          => 'Peter Visser',
                    'bedrijfsnaam'  => 'Aannemer Visser & Zonen',
                    'emailadres'    => 'peter@visseraannemer.nl',
                    'telefoon'      => '0687654321',
                    'straatnaam'    => 'Hoofdweg 88B',
                    'postcode'      => '9765 AA',
                    'plaats'        => 'Groningen',
                    'omschrijving'  => 'Vervanging houten paalfundering. Woning uit 1925, ernstige verzakking.',
                    'hoogte'        => '5000 mm',
                    'startdatum'    => '1 april 2026',
                    'form_title'    => 'Maatwerk offerte aanvraag',
                    'submitted_at'  => Carbon::now()->subDays(5)->toDateTimeString(),
                ],
            ],
            [
                'company'     => 'Particulier Van der Berg',
                'name'        => 'Sandra van der Berg',
                'email'       => 'sandra.vdberg@gmail.com',
                'phone'       => '0641122334',
                'street'      => 'Rozenlaan',
                'house_number'=> '4',
                'postal_code' => '2343 CD',
                'city'        => 'Oegstgeest',
                'status'      => LeadStatus::InfoRequested,
                'notes'       => "Vrijstaande schuur, 6x8m. Betonvloer plaatsen op schroefpalen.",
                'request_data'=> [
                    'naam'          => 'Sandra van der Berg',
                    'emailadres'    => 'sandra.vdberg@gmail.com',
                    'telefoon'      => '0641122334',
                    'straatnaam'    => 'Rozenlaan 4',
                    'postcode'      => '2343 CD',
                    'plaats'        => 'Oegstgeest',
                    'omschrijving'  => 'Vrijstaande schuur, 6x8m. Betonvloer plaatsen op schroefpalen.',
                    'aanvullend'    => 'Kleigrond, grondwaterstand hoog.',
                    'form_title'    => 'Offerte aanvraag',
                    'submitted_at'  => Carbon::now()->subDays(8)->toDateTimeString(),
                ],
            ],
            [
                'company'     => 'Renovatiebedrijf Ten Hag',
                'name'        => 'Johan ten Hag',
                'email'       => 'j.tenhag@renovatietenhag.nl',
                'phone'       => '0655443322',
                'street'      => 'Industriepark',
                'house_number'=> '33',
                'postal_code' => '7553 LJ',
                'city'        => 'Hengelo',
                'status'      => LeadStatus::Quoted,
                'notes'       => "Nieuwbouw bedrijfspand. 40 schroefpalen gewenst, zware constructie.",
                'request_data'=> [
                    'naam'          => 'Johan ten Hag',
                    'bedrijfsnaam'  => 'Renovatiebedrijf Ten Hag',
                    'emailadres'    => 'j.tenhag@renovatietenhag.nl',
                    'telefoon'      => '0655443322',
                    'straatnaam'    => 'Industriepark 33',
                    'postcode'      => '7553 LJ',
                    'plaats'        => 'Hengelo',
                    'omschrijving'  => 'Nieuwbouw bedrijfspand. 40 schroefpalen gewenst, zware constructie.',
                    'gewicht'       => '45000 kg',
                    'hoogte'        => '7500 mm',
                    'startdatum'    => '15 mei 2026',
                    'locatie'       => 'Industrieterrein, goed bereikbaar met kraan',
                    'form_title'    => 'Maatwerk offerte aanvraag',
                    'submitted_at'  => Carbon::now()->subDays(12)->toDateTimeString(),
                ],
            ],
            [
                'company'     => 'Spurt Digital (test)',
                'name'        => 'Kevin Brinkman',
                'email'       => 'kevin@spurt.digital',
                'phone'       => '0612345000',
                'street'      => 'Teststraat',
                'house_number'=> '1',
                'postal_code' => '1234 AB',
                'city'        => 'Teststad',
                'status'      => LeadStatus::New,
                'notes'       => "Testlead voor development. Inkooporders en projectflow uittesten.",
                'request_data'=> [
                    'naam'         => 'Kevin Brinkman',
                    'bedrijfsnaam' => 'Spurt Digital (test)',
                    'emailadres'   => 'kevin@spurt.digital',
                    'telefoon'     => '0612345000',
                    'straatnaam'   => 'Teststraat 1',
                    'postcode'     => '1234 AB',
                    'plaats'       => 'Teststad',
                    'omschrijving' => 'Testlead voor development.',
                    'form_title'   => 'Testformulier',
                    'submitted_at' => Carbon::now()->toDateTimeString(),
                ],
            ],
        ];

        foreach ($leads as $data) {
            $company = Company::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'         => $data['company'],
                    'phone'        => $data['phone'],
                    'street'       => $data['street'],
                    'house_number' => $data['house_number'],
                    'postal_code'  => $data['postal_code'],
                    'city'         => $data['city'],
                ]
            );

            Lead::create([
                'company_id'   => $company->id,
                'name'         => $data['name'],
                'email'        => $data['email'],
                'phone'        => $data['phone'],
                'street'       => $data['street'],
                'house_number' => $data['house_number'],
                'postal_code'  => $data['postal_code'],
                'city'         => $data['city'],
                'status'       => $data['status'],
                'notes'        => $data['notes'],
                'request_data' => $data['request_data'],
            ]);

            $this->line("  Lead: {$data['name']} ({$data['email']}) — {$data['status']->getLabel()}");
        }
    }
}
