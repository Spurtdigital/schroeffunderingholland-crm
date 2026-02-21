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

        // 2. Create products (30 producten)
        $this->command->info('📦 Creating products...');
        $products = Product::factory()->count(30)->create();

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
                        ->for($product, 'itemable')  // Specify morphTo relationship name
                        ->create([
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

        // 6. Create projects from approved quotes
        $this->command->info('🔧 Creating projects...');
        $approvedQuotes = Quote::where('status', QuoteStatus::Approved)->get();

        foreach ($approvedQuotes->random(min(5, $approvedQuotes->count())) as $quote) {
            $project = Project::factory()
                ->for($quote->company)
                ->for($quote)
                ->create([
                    'status' => fake()->randomElement([
                        ProjectStatus::Active,
                        ProjectStatus::Active,
                        ProjectStatus::Completed,
                    ]),
                    'project_value' => $quote->total,
                ]);

            // Mark quote as converted
            $quote->update([
                'status' => QuoteStatus::Converted,
                'project_id' => $project->id,
            ]);

            // Create invoice(s) for project
            $this->command->info('💰 Creating invoices...');
            if ($project->invoicing_type === \App\Enums\InvoicingType::Split5050 || $project->invoicing_type === \App\Enums\InvoicingType::Split3070) {
                $firstPct = $project->invoicing_type->getFirstInvoicePercentage() / 100;
                $secondPct = $project->invoicing_type->getSecondInvoicePercentage() / 100;
                $projectValue = $quote->total ?? 0;

                // Deposit invoice
                Invoice::factory()
                    ->for($project)
                    ->for($project->company)
                    ->create([
                        'invoice_type' => \App\Enums\InvoiceType::Deposit,
                        'subtotal' => round($projectValue * $firstPct / 1.21, 2),
                        'vat_amount' => round($projectValue * $firstPct - $projectValue * $firstPct / 1.21, 2),
                        'total' => round($projectValue * $firstPct, 2),
                        'status' => InvoiceStatus::Sent,
                    ]);

                // Final invoice - alleen als project completed
                if ($project->status === ProjectStatus::Completed) {
                    Invoice::factory()
                        ->for($project)
                        ->for($project->company)
                        ->create([
                            'invoice_type' => \App\Enums\InvoiceType::Final,
                            'subtotal' => round($projectValue * $secondPct / 1.21, 2),
                            'vat_amount' => round($projectValue * $secondPct - $projectValue * $secondPct / 1.21, 2),
                            'total' => round($projectValue * $secondPct, 2),
                            'status' => fake()->randomElement([
                                InvoiceStatus::Draft,
                                InvoiceStatus::Sent,
                            ]),
                        ]);
                }
            } else {
                // Single invoice
                $projectValue = $quote->total ?? 0;
                Invoice::factory()
                    ->for($project)
                    ->for($project->company)
                    ->create([
                        'invoice_type' => \App\Enums\InvoiceType::Single,
                        'subtotal' => round($projectValue / 1.21, 2),
                        'vat_amount' => round($projectValue - $projectValue / 1.21, 2),
                        'total' => round($projectValue, 2),
                        'status' => $project->status === ProjectStatus::Completed
                            ? InvoiceStatus::Sent
                            : InvoiceStatus::Draft,
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
                ['Invoices', Invoice::count()],
            ]
        );
        $this->command->info('');
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   Email: admin@sfh.nl');
        $this->command->info('   Password: password');
    }
}
