<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Services\QuoteCalculationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding database with demo data...');

        // 1. Create admin user (firstOrCreate zodat re-seeden niet crasht)
        $this->command->info('👤 Creating admin user...');
        $admin = User::firstOrCreate(
            ['email' => 'admin@sfh.nl'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]);

        // Assign super_admin role to the admin user
        $this->command->info('🔐 Assigning super_admin role...');
        $superAdminRoleName = \BezhanSalleh\FilamentShield\Support\Utils::getSuperAdminName();
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => $superAdminRoleName,
            'guard_name' => 'web',
        ]);
        $admin->assignRole($role);

        // 2. Maak alle echte SFH-producten aan via updateOrCreate
        $this->command->info('📦 Creating products...');
        $products = collect();
        foreach (\Database\Factories\ProductFactory::catalog() as $productData) {
            $product = \App\Models\Product::updateOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
            $products->push($product);
        }

        // 3. Create companies (15 klanten)
        $this->command->info('🏢 Creating companies...');
        $companies = Company::factory()->count(15)->create();

        // 4. Create leads for each company (25 leads totaal)
        $this->command->info('🧑‍🤝‍🧑 Creating leads...');
        foreach ($companies->random(10) as $company) {
            Lead::factory()
                ->count(rand(1, 3))
                ->for($company)
                ->create();
        }

        // 5. Create quotes with realistic workflow
        $this->command->info('📄 Creating quotes with items...');
        $quoteService = app(QuoteCalculationService::class);

        foreach ($companies as $company) {
            // Elk bedrijf krijgt 1-2 offertes
            $quoteCount = rand(1, 2);

            for ($i = 0; $i < $quoteCount; $i++) {
                $lead = $company->leads()->inRandomOrder()->first();

                // Mix van draft, sent, approved quotes
                $statusOptions = [
                    QuoteStatus::Draft,
                    QuoteStatus::Draft,
                    QuoteStatus::Sent,
                    QuoteStatus::Sent,
                    QuoteStatus::Approved,
                ];

                $quoteFactory = Quote::factory()->for($company);

                // Only attach lead if one exists for this company
                if ($lead) {
                    $quoteFactory->for($lead);
                }

                $quote = $quoteFactory->create([
                    'status' => $statusOptions[array_rand($statusOptions)],
                ]);

                // Add 3-7 quote items per quote
                $itemCount = rand(3, 7);
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->random();

                    QuoteItem::factory()
                        ->for($quote)
                        ->for($product, 'itemable')
                        ->create([
                            'type' => \App\Enums\QuoteItemType::Product,
                            'description' => $product->name,
                            'quantity' => rand(1, 10),
                            'unit_price' => $product->sales_price,
                        ]);
                }

                // Recalculate quote totals
                $quoteService->calculateTotals($quote);

                // Mark lead as converted if quote is approved
                if ($quote->status === QuoteStatus::Approved && $lead) {
                    $lead->update([
                        'status' => LeadStatus::Converted,
                        'converted_to_quote_id' => $quote->id,
                    ]);
                }
            }
        }

        // 6. Create projects — vaste projecten met datums 20 feb t/m 15 mrt 2026
        // zodat de dashboard-widget "Komende projecten" altijd gevuld is.
        $this->command->info('🔧 Creating projects...');
        $quoteService = app(\App\Services\QuoteCalculationService::class);

        $projectDefinitions = [
            [
                'company_name' => 'Bouwbedrijf De Vries & Zn.',
                'start_date' => '2026-02-20',
                'completion_date' => '2026-02-23',
                'project_value' => 8750.00,
                'invoicing_type' => \App\Enums\InvoicingType::Split5050,
                'status' => ProjectStatus::Active,
                'notes' => 'Funderingsherstel achtergevel jaren-30 woning Rotterdam-Zuid. 12× ø89mm schroefpalen.',
            ],
            [
                'company_name' => 'Aannemingsbedrijf Hartman B.V.',
                'start_date' => '2026-02-24',
                'completion_date' => '2026-02-26',
                'project_value' => 14200.00,
                'invoicing_type' => \App\Enums\InvoicingType::Split5050,
                'status' => ProjectStatus::Active,
                'notes' => 'Nieuwbouw aanbouw 60 m² Schiedam. 18× ø114mm schroefpalen, diepte 6m.',
            ],
            [
                'company_name' => 'Woningbouw Zuidwest Delft',
                'start_date' => '2026-02-28',
                'completion_date' => '2026-03-03',
                'project_value' => 22500.00,
                'invoicing_type' => \App\Enums\InvoicingType::Split3070,
                'status' => ProjectStatus::Active,
                'notes' => 'Funderingsherstel 4 woningen Delft-Noord. Totaal 48× ø114mm palen. Inclusief funderingsonderzoek.',
            ],
            [
                'company_name' => 'Vastgoed Rijnmond Groep',
                'start_date' => '2026-03-04',
                'completion_date' => '2026-03-07',
                'project_value' => 11800.00,
                'invoicing_type' => \App\Enums\InvoicingType::Single,
                'status' => ProjectStatus::Active,
                'notes' => 'Fundering bedrijfshal uitbreiding Ridderkerk. 16× ø114mm schroefpalen.',
            ],
            [
                'company_name' => 'Projectontwikkeling IJsselmond',
                'start_date' => '2026-03-09',
                'completion_date' => '2026-03-12',
                'project_value' => 31000.00,
                'invoicing_type' => \App\Enums\InvoicingType::Split5050,
                'status' => ProjectStatus::Active,
                'notes' => 'Nieuwbouw 6 grondgebonden woningen Gouda. 72× ø89mm schroefpalen. Grote opdracht.',
            ],
            [
                'company_name' => 'Renovatiebedrijf Van Tol',
                'start_date' => '2026-03-11',
                'completion_date' => '2026-03-13',
                'project_value' => 6400.00,
                'invoicing_type' => \App\Enums\InvoicingType::Single,
                'status' => ProjectStatus::Active,
                'notes' => 'Terrasoverkapping fundering Rotterdam-Oost. 8× ø89mm schroefpalen.',
            ],
            [
                'company_name' => 'Bouwgroep Hollands Diep',
                'start_date' => '2026-03-13',
                'completion_date' => '2026-03-15',
                'project_value' => 18900.00,
                'invoicing_type' => \App\Enums\InvoicingType::Split3070,
                'status' => ProjectStatus::Active,
                'notes' => 'Funderingsherstel monumentaal pand Dordrecht. 24× ø168mm zware schroefpalen, diepte 8m.',
            ],
        ];

        foreach ($projectDefinitions as $def) {
            // Bedrijf aanmaken
            $projectCompany = Company::firstOrCreate(
                ['name' => $def['company_name']],
                [
                    'email' => fake()->randomElement(['kevin@spurt.digital', 'bob@schroeffunderingholland.nl']),
                    'phone' => '010-'.rand(1000000, 9999999),
                ]
            );

            // Bijbehorende offerte aanmaken (approved)
            $quote = Quote::factory()->for($projectCompany)->create([
                'status' => QuoteStatus::Converted,
                'subtotal' => round($def['project_value'] / 1.21, 2),
                'vat_amount' => round($def['project_value'] - $def['project_value'] / 1.21, 2),
                'total' => $def['project_value'],
                'valid_until' => now()->addDays(30),
            ]);

            // Items toevoegen aan de offerte
            $selectedProducts = $products->random(rand(3, 5));
            foreach ($selectedProducts as $product) {
                QuoteItem::factory()->for($quote)->for($product, 'itemable')->create([
                    'type' => \App\Enums\QuoteItemType::Product,
                    'description' => $product->name,
                    'quantity' => rand(4, 24),
                    'unit_price' => $product->sales_price,
                ]);
            }
            $quoteService->calculateTotals($quote);

            // Project aanmaken
            $project = Project::create([
                'company_id' => $projectCompany->id,
                'quote_id' => $quote->id,
                'status' => $def['status'],
                'invoicing_type' => $def['invoicing_type'],
                'start_date' => $def['start_date'],
                'completion_date' => $def['completion_date'],
                'project_value' => $def['project_value'],
                'notes' => $def['notes'],
            ]);

            $quote->update(['project_id' => $project->id]);

            // Kopieer quote items naar project items (zelfde als ConvertToProjectAction)
            foreach ($quote->items as $quoteItem) {
                $project->items()->create([
                    'quote_item_id' => $quoteItem->id,
                    'itemable_type' => $quoteItem->itemable_type,
                    'itemable_id' => $quoteItem->itemable_id,
                    'type' => $quoteItem->type,
                    'description' => $quoteItem->description,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'total' => $quoteItem->total,
                    'sort_order' => $quoteItem->sort_order,
                ]);
            }

            // Factuur aanmaken
            $this->command->info('💰 Creating invoice for '.$def['company_name'].'...');
            $projectValue = $def['project_value'];

            if (in_array($def['invoicing_type'], [\App\Enums\InvoicingType::Split5050, \App\Enums\InvoicingType::Split3070])) {
                $firstPct = $def['invoicing_type']->getFirstInvoicePercentage() / 100;
                Invoice::factory()->for($project)->for($projectCompany)->create([
                    'invoice_type' => \App\Enums\InvoiceType::Deposit,
                    'subtotal' => round($projectValue * $firstPct / 1.21, 2),
                    'vat_amount' => round($projectValue * $firstPct - $projectValue * $firstPct / 1.21, 2),
                    'total' => round($projectValue * $firstPct, 2),
                    'status' => InvoiceStatus::Sent,
                ]);
            } else {
                Invoice::factory()->for($project)->for($projectCompany)->create([
                    'invoice_type' => \App\Enums\InvoiceType::Single,
                    'subtotal' => round($projectValue / 1.21, 2),
                    'vat_amount' => round($projectValue - $projectValue / 1.21, 2),
                    'total' => round($projectValue, 2),
                    'status' => InvoiceStatus::Draft,
                ]);
            }
        }

        // Summary
        $this->command->info('');
        $this->command->info('✅ Seeding complete!');
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Users', User::count()],
                ['Companies', Company::count()],
                ['Leads', Lead::count()],
                ['Products', Product::count()],
                ['Quotes', Quote::count()],
                ['Quote Items', QuoteItem::count()],
                ['Projects', Project::count()],
                ['Project Items', ProjectItem::count()],
                ['Invoices', Invoice::count()],
            ]
        );
        $this->command->info('');
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   Email: admin@sfh.nl');
        $this->command->info('   Password: password');
    }
}
