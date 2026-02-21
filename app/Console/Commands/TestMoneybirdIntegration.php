<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\MoneybirdService;
use Illuminate\Console\Command;

class TestMoneybirdIntegration extends Command
{
    protected $signature = 'test:moneybird-integration';
    protected $description = 'Test Moneybird integration with company sync';

    public function handle()
    {
        $this->info('=== Test Moneybird Integration ===');
        
        $moneybirdService = app(MoneybirdService::class);

        // Test 1: Check if configured
        $this->newLine();
        $this->info('Test 1: Check Configuration');
        if ($moneybirdService->isConfigured()) {
            $this->info('✅ Moneybird is configured');
        } else {
            $this->warn('⚠️  Moneybird is NOT configured - skipping API tests');
            $this->line('   Please configure Moneybird API credentials in Settings');
            return 0;
        }

        // Test 2: Test connection
        $this->newLine();
        $this->info('Test 2: Test Connection');
        $result = $moneybirdService->testConnection();
        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
        } else {
            $this->error('❌ ' . $result['message']);
            return 1;
        }

        // Test 3: Get Moneybird instance
        $this->newLine();
        $this->info('Test 3: Get Moneybird Instance');
        try {
            $moneybird = $moneybirdService->getMoneybirdInstance();
            if ($moneybird) {
                $this->info('✅ Moneybird instance created successfully');
                
                // Get first 5 contacts
                $contacts = $moneybird->contact()->get();
                $contactCount = is_array($contacts) ? count($contacts) : $contacts->count();
                $this->line("   Total contacts available: {$contactCount}");
                
                $this->newLine();
                $this->line('   First 5 contacts:');
                $count = 0;
                foreach ($contacts as $contact) {
                    if ($count >= 5) break;
                    $name = $contact->company_name ?: ($contact->firstname . ' ' . $contact->lastname);
                    $this->line("   - ID {$contact->id}: {$name} ({$contact->email})");
                    $count++;
                }
            } else {
                $this->error('❌ Failed to create Moneybird instance');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        // Test 4: Test importing a contact
        $this->newLine();
        if ($this->confirm('Test importing a Moneybird contact?', false)) {
            $contactId = $this->ask('Enter Moneybird contact ID to import');
            
            try {
                $company = $moneybirdService->importContact($contactId);
                $this->info('✅ Contact imported successfully');
                $this->line("   Company ID: {$company->id}");
                $this->line("   Name: {$company->name}");
                $this->line("   Email: {$company->email}");
                $this->line("   Address: {$company->street} {$company->house_number}");
                $this->line("   Postal/City: {$company->postal_code} {$company->city}");
                $this->line("   Moneybird ID: {$company->moneybird_contact_id}");
                
                if ($this->confirm('Delete imported test company?', true)) {
                    $company->delete();
                    $this->info('✅ Test company deleted');
                }
            } catch (\Exception $e) {
                $this->error('❌ Import failed: ' . $e->getMessage());
            }
        }

        // Test 5: Test syncing a company to Moneybird
        $this->newLine();
        if ($this->confirm('Test sending a new company to Moneybird?', false)) {
            try {
                // Create test company
                $company = Company::create([
                    'name' => 'Test Sync Company ' . date('Y-m-d H:i:s'),
                    'email' => 'testsync@example.nl',
                    'phone' => '0612345678',
                    'street' => 'Teststraat',
                    'house_number' => '999',
                    'postal_code' => '1234AB',
                    'city' => 'Amsterdam',
                ]);

                $this->info("✅ Created test company ID: {$company->id}");
                
                // Sync to Moneybird
                $this->line('   Syncing to Moneybird...');
                $success = $moneybirdService->syncCompany($company);
                
                if ($success) {
                    $company->refresh();
                    $this->info('✅ Company synced to Moneybird');
                    $this->line("   Moneybird Contact ID: {$company->moneybird_contact_id}");
                    
                    if ($this->confirm('Delete test company? (will NOT delete from Moneybird)', true)) {
                        $company->delete();
                        $this->info('✅ Test company deleted from local database');
                        $this->warn('⚠️  Contact still exists in Moneybird with ID: ' . $company->moneybird_contact_id);
                    }
                } else {
                    $this->error('❌ Sync failed');
                    $company->delete();
                }
            } catch (\Exception $e) {
                $this->error('❌ Sync failed: ' . $e->getMessage());
                if (isset($company) && $company->exists) {
                    $company->delete();
                }
            }
        }

        $this->newLine();
        $this->info('✅ All Moneybird integration tests completed');
        
        return 0;
    }
}
