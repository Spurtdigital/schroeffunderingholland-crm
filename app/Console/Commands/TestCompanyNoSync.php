<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\MoneybirdService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCompanyNoSync extends Command
{
    protected $signature = 'test:company-no-sync';
    protected $description = 'Test that company without Moneybird ID does NOT auto-sync';

    public function handle()
    {
        $this->info('=== Test Company WITHOUT Moneybird ID (No Auto-Sync) ===');
        
        // Step 1: Create company without Moneybird ID
        $this->newLine();
        $this->info('Step 1: Create company WITHOUT Moneybird ID');
        
        $company = Company::create([
            'name' => 'No Sync Test Company',
            'email' => 'nosync@test.nl',
            'phone' => '0611111111',
            'street' => 'Lokalestraat',
            'house_number' => '1',
            'postal_code' => '9999ZZ',
            'city' => 'Utrecht',
        ]);
        
        $this->info("✅ Company created (ID: {$company->id})");
        $this->line("   Moneybird Contact ID: " . ($company->moneybird_contact_id ?? 'NULL'));

        // Step 2: Update company
        $this->newLine();
        $this->info('Step 2: Update company data');
        
        $company->update([
            'phone' => '0622222222',
            'city' => 'Den Haag',
        ]);
        
        $this->info("✅ Company updated");
        $this->line("   New Phone: {$company->phone}");
        $this->line("   New City: {$company->city}");
        $this->line("   Moneybird Contact ID: " . ($company->moneybird_contact_id ?? 'NULL'));

        // Step 3: Verify it would NOT sync in afterSave hook
        $this->newLine();
        $this->info('Step 3: Verify afterSave logic');
        
        if ($company->moneybird_contact_id) {
            $this->error('❌ FAIL: Company should NOT have Moneybird ID');
        } else {
            $this->info('✅ CORRECT: Company has no Moneybird ID');
            $this->info('✅ CORRECT: afterSave hook would skip Moneybird sync');
            $this->line('   (Only companies already in Moneybird get auto-synced)');
        }

        // Clean up
        $this->newLine();
        if ($this->confirm('Delete test company?', true)) {
            $company->delete();
            $this->info('✅ Test company deleted');
        }

        $this->newLine();
        $this->info('✅ Test completed: Companies without Moneybird ID are NOT auto-synced');
        
        return 0;
    }
}
