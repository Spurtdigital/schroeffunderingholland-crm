<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\MoneybirdService;
use Illuminate\Console\Command;

class TestCompanyAutoSync extends Command
{
    protected $signature = 'test:company-auto-sync';
    protected $description = 'Test automatic Moneybird sync when company is updated';

    public function handle()
    {
        $this->info('=== Test Company Auto-Sync to Moneybird ===');
        
        $moneybirdService = app(MoneybirdService::class);

        // Check if configured
        if (!$moneybirdService->isConfigured()) {
            $this->warn('⚠️  Moneybird is NOT configured - cannot test sync');
            return 0;
        }

        $this->newLine();
        $this->info('Step 1: Create a test company locally');
        
        $company = Company::create([
            'name' => 'Auto Sync Test BV ' . date('H:i:s'),
            'email' => 'autosync@test.nl',
            'phone' => '0612345678',
            'street' => 'Syncstraat',
            'house_number' => '100',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
        ]);
        
        $this->info("✅ Company created (ID: {$company->id})");
        $this->line("   Moneybird Contact ID: " . ($company->moneybird_contact_id ?? 'NULL'));

        // Step 2: Sync to Moneybird (first time)
        $this->newLine();
        $this->info('Step 2: Initial sync to Moneybird');
        
        try {
            $moneybirdService->syncCompany($company);
            $company->refresh();
            $this->info("✅ Company synced to Moneybird");
            $this->line("   Moneybird Contact ID: {$company->moneybird_contact_id}");
        } catch (\Exception $e) {
            $this->error('❌ Initial sync failed: ' . $e->getMessage());
            $company->delete();
            return 1;
        }

        // Step 3: Get current data from Moneybird
        $this->newLine();
        $this->info('Step 3: Verify data in Moneybird');
        
        try {
            $moneybird = $moneybirdService->getMoneybirdInstance();
            $contact = $moneybird->contact()->find($company->moneybird_contact_id);
            
            $this->line("   Company Name: {$contact->company_name}");
            $this->line("   Email: {$contact->email}");
            $this->line("   Phone: {$contact->phone}");
            $this->line("   Address: {$contact->address1} {$contact->address2}");
            $this->line("   Postal/City: {$contact->zipcode} {$contact->city}");
        } catch (\Exception $e) {
            $this->error('❌ Could not fetch from Moneybird: ' . $e->getMessage());
        }

        // Step 4: Update company locally
        $this->newLine();
        $this->info('Step 4: Update company data locally');
        
        $newPhone = '0698765432';
        $newStreet = 'Nieuwe Straat';
        $newHouseNumber = '999';
        $newCity = 'Rotterdam';
        
        $company->update([
            'phone' => $newPhone,
            'street' => $newStreet,
            'house_number' => $newHouseNumber,
            'city' => $newCity,
        ]);
        
        $this->info("✅ Company updated locally");
        $this->line("   New Phone: {$newPhone}");
        $this->line("   New Address: {$newStreet} {$newHouseNumber}");
        $this->line("   New City: {$newCity}");

        // Step 5: Manually sync to Moneybird (simulating afterSave)
        $this->newLine();
        $this->info('Step 5: Sync updated data to Moneybird');
        
        try {
            $moneybirdService->syncCompany($company);
            $this->info("✅ Updated data synced to Moneybird");
        } catch (\Exception $e) {
            $this->error('❌ Update sync failed: ' . $e->getMessage());
        }

        // Step 6: Verify update in Moneybird
        $this->newLine();
        $this->info('Step 6: Verify updated data in Moneybird');
        
        try {
            $moneybird = $moneybirdService->getMoneybirdInstance();
            $contact = $moneybird->contact()->find($company->moneybird_contact_id);
            
            $this->line("   Phone: {$contact->phone}");
            $this->line("   Address: {$contact->address1} {$contact->address2}");
            $this->line("   City: {$contact->city}");
            
            // Check if values match
            $phoneMatch = $contact->phone === $newPhone;
            $streetMatch = $contact->address1 === $newStreet;
            $houseNumberMatch = $contact->address2 === $newHouseNumber;
            $cityMatch = $contact->city === $newCity;
            
            $this->newLine();
            if ($phoneMatch && $streetMatch && $houseNumberMatch && $cityMatch) {
                $this->info('✅✅✅ SUCCESS: All fields updated correctly in Moneybird!');
            } else {
                $this->warn('⚠️  PARTIAL UPDATE:');
                $this->line("   Phone match: " . ($phoneMatch ? '✅' : '❌'));
                $this->line("   Street match: " . ($streetMatch ? '✅' : '❌'));
                $this->line("   House number match: " . ($houseNumberMatch ? '✅' : '❌'));
                $this->line("   City match: " . ($cityMatch ? '✅' : '❌'));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Could not verify in Moneybird: ' . $e->getMessage());
        }

        // Clean up
        $this->newLine();
        if ($this->confirm('Delete test company? (will NOT delete from Moneybird)', true)) {
            $moneybirdContactId = $company->moneybird_contact_id;
            $company->delete();
            $this->info('✅ Test company deleted from local database');
            $this->warn("⚠️  Contact still exists in Moneybird with ID: {$moneybirdContactId}");
            $this->line('   You can manually delete it in Moneybird if needed');
        }

        return 0;
    }
}
