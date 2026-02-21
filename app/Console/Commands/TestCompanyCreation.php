<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class TestCompanyCreation extends Command
{
    protected $signature = 'test:company-creation';
    protected $description = 'Test company creation with correct database fields';

    public function handle()
    {
        $this->info('=== Test 1: Manual Company Creation ===');
        
        try {
            $company = Company::create([
                'name' => 'Test Company Manual',
                'email' => 'test@manual.nl',
                'phone' => '0612345678',
                'street' => 'Teststraat',
                'house_number' => '123',
                'postal_code' => '1234AB',
                'city' => 'Amsterdam',
            ]);

            $this->info("✅ Created company ID: {$company->id}");
            $this->line("   Name: {$company->name}");
            $this->line("   Email: {$company->email}");
            $this->line("   Phone: {$company->phone}");
            $this->line("   Address: {$company->street} {$company->house_number}");
            $this->line("   Postal/City: {$company->postal_code} {$company->city}");
            $this->line("   Moneybird ID: " . ($company->moneybird_contact_id ?? 'NULL'));

            // Verify it's in database
            $verify = Company::find($company->id);
            if ($verify) {
                $this->info('✅ Verification - Found in database');
            } else {
                $this->error('❌ Verification - NOT found in database');
            }

            // Test relationships
            $this->newLine();
            $this->info('=== Test 2: Relationships ===');
            $this->line("   Leads: " . $company->leads()->count());
            $this->line("   Quotes: " . $company->quotes()->count());
            $this->line("   Projects: " . $company->projects()->count());

            // Clean up
            $this->newLine();
            if ($this->confirm('Delete test company?', true)) {
                $company->delete();
                $this->info('✅ Test company deleted');
            }

            $this->newLine();
            $this->info('✅ All tests passed - no database errors!');
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('   File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }
}
