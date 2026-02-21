<?php

namespace App\Console\Commands;

use App\Enums\LeadStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Console\Command;

class TestCompleteWorkflow extends Command
{
    protected $signature = 'test:complete-workflow';
    protected $description = 'Test complete workflow: Company → Lead → Quote → Project';

    public function handle()
    {
        $this->info('=== Test Complete CRM Workflow ===');
        
        try {
            // Step 1: Create Company
            $this->newLine();
            $this->info('Step 1: Create Company');
            $company = Company::create([
                'name' => 'Workflow Test BV',
                'email' => 'workflow@test.nl',
                'phone' => '0612345678',
                'street' => 'Workflowstraat',
                'house_number' => '42',
                'postal_code' => '1234AB',
                'city' => 'Amsterdam',
            ]);
            $this->info("✅ Company created (ID: {$company->id})");

            // Step 2: Create Lead
            $this->newLine();
            $this->info('Step 2: Create Lead');
            $lead = Lead::create([
                'company_id' => $company->id,
                'name' => 'Test Lead',
                'email' => $company->email,
                'phone' => $company->phone,
                'street' => $company->street,
                'postal_code' => $company->postal_code,
                'city' => $company->city,
                'notes' => 'Test workflow lead',
                'status' => LeadStatus::New,
            ]);
            $this->info("✅ Lead created (ID: {$lead->id})");

            // Step 3: Create Quote
            $this->newLine();
            $this->info('Step 3: Create Quote');
            $quote = Quote::create([
                'company_id' => $company->id,
                'quote_number' => 'Q-' . date('YmdHis'),
                'quote_date' => now(),
                'valid_until' => now()->addDays(30),
                'subtotal' => 1000.00,
                'vat_rate' => 21.00,
                'vat_amount' => 210.00,
                'total' => 1210.00,
                'status' => QuoteStatus::Draft,
            ]);
            $this->info("✅ Quote created (ID: {$quote->id}, Number: {$quote->quote_number})");

            // Step 4: Add Quote Items
            $this->newLine();
            $this->info('Step 4: Add Quote Items');
            
            // Get or create test product
            $product = Product::firstOrCreate(
                ['sku' => 'TEST-SKU'],
                [
                    'name' => 'Test Product',
                    'description' => 'Test product for workflow',
                    'price' => 100.00,
                    'stock' => 10,
                ]
            );
            
            $quote->items()->create([
                'product_id' => $product->id,
                'description' => 'Test Product',
                'quantity' => 5,
                'unit_price' => 100.00,
                'total' => 500.00,
            ]);
            $this->info("✅ Quote item added (Product: {$product->name})");

            // Step 5: Convert to Project
            $this->newLine();
            $this->info('Step 5: Convert Quote to Project');
            $project = Project::create([
                'company_id' => $company->id,
                'quote_id' => $quote->id,
                'project_number' => 'P-' . date('YmdHis'),
                'name' => 'Test Project',
                'description' => 'Test project from workflow',
                'start_date' => now(),
                'status' => ProjectStatus::Active,
                'total_amount' => $quote->total,
            ]);
            
            // Update quote status
            $quote->update(['status' => QuoteStatus::Approved]);
            
            $this->info("✅ Project created (ID: {$project->id}, Number: {$project->project_number})");

            // Verification
            $this->newLine();
            $this->info('=== Verification ===');
            $this->line("Company has {$company->leads()->count()} lead(s)");
            $this->line("Company has {$company->quotes()->count()} quote(s)");
            $this->line("Company has {$company->projects()->count()} project(s)");
            $this->line("Quote has {$quote->items()->count()} item(s)");
            $this->line("Quote status: {$quote->status->getLabel()}");
            $this->line("Project status: {$project->status->getLabel()}");

            // Check all database fields
            $this->newLine();
            $this->info('=== Database Fields Check ===');
            $company->refresh();
            $this->line("Company fields present:");
            $this->line("  - name: " . ($company->name ? '✅' : '❌'));
            $this->line("  - email: " . ($company->email ? '✅' : '❌'));
            $this->line("  - phone: " . ($company->phone ? '✅' : '❌'));
            $this->line("  - street: " . ($company->street ? '✅' : '❌'));
            $this->line("  - house_number: " . ($company->house_number ? '✅' : '❌'));
            $this->line("  - postal_code: " . ($company->postal_code ? '✅' : '❌'));
            $this->line("  - city: " . ($company->city ? '✅' : '❌'));

            // Clean up
            $this->newLine();
            if ($this->confirm('Delete all test data?', true)) {
                $project->delete();
                $quote->items()->delete();
                $quote->delete();
                $lead->delete();
                $company->delete();
                if ($product->sku === 'TEST-SKU') {
                    $product->delete();
                }
                $this->info('✅ All test data deleted');
            }

            $this->newLine();
            $this->info('✅✅✅ Complete workflow test PASSED - No database errors! ✅✅✅');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Workflow test FAILED');
            $this->error('Error: ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
            
            // Clean up on error
            if (isset($project) && $project->exists) $project->delete();
            if (isset($quote) && $quote->exists) {
                $quote->items()->delete();
                $quote->delete();
            }
            if (isset($lead) && $lead->exists) $lead->delete();
            if (isset($company) && $company->exists) $company->delete();
            if (isset($product) && $product->exists && $product->sku === 'TEST-SKU') {
                $product->delete();
            }
            
            return 1;
        }
    }
}
