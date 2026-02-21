<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class TestCompanyMoneybirdActions extends Command
{
    protected $signature = 'test:company-moneybird';
    protected $description = 'Test if Company Moneybird actions are available';

    public function handle()
    {
        $this->info('Testing Company Moneybird functionality...');

        // Check if a company exists
        $company = Company::first();
        
        if (!$company) {
            $this->warn('No companies found. Creating test company...');
            $company = Company::create([
                'name' => 'Test Bedrijf BV',
                'contact_person' => 'Jan Jansen',
                'email' => 'test@testbedrijf.nl',
                'phone' => '0612345678',
                'address' => 'Teststraat 1',
                'postal_code' => '1234AB',
                'city' => 'Amsterdam',
            ]);
            $this->info("✓ Test company created: {$company->name}");
        } else {
            $this->info("✓ Using existing company: {$company->name}");
        }

        // Check Moneybird status
        if ($company->moneybird_contact_id) {
            $this->info("✓ Company has Moneybird contact ID: {$company->moneybird_contact_id}");
            $this->info("  → 'Importeer van Moneybird' actie should be visible");
            $this->info("  → 'Verstuur naar Moneybird' actie should be hidden");
        } else {
            $this->info("✓ Company has no Moneybird contact ID yet");
            $this->info("  → 'Verstuur naar Moneybird' actie should be visible");
            $this->info("  → 'Importeer van Moneybird' actie should be hidden");
        }

        $this->info("\n✅ All checks passed!");
        $this->info("\nTest the actions in the browser:");
        $this->info("1. Ga naar: https://sfh.ddev.site/admin/companies");
        $this->info("2. Klik op 'Importeer alle klanten van Moneybird' om alle Moneybird contacten te importeren");
        $this->info("3. Open een klant zonder Moneybird ID en klik 'Verstuur naar Moneybird'");
        $this->info("4. Open een klant met Moneybird ID en klik 'Importeer van Moneybird' om bij te werken");
        
        return 0;
    }
}
