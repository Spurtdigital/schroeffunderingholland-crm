<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\LeadWebhookController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestLeadWebhook extends Command
{
    protected $signature = 'test:lead-webhook';
    protected $description = 'Test Lead webhook with company creation';

    public function handle()
    {
        $this->info('=== Test Lead Webhook (CF7) ===');
        
        // Test 1: Complete webhook data
        $this->newLine();
        $this->info('Test 1: Complete webhook data with all fields');
        
        $testData = [
            'bedrijfsnaam' => 'Test Bedrijf Via Webhook',
            'naam' => 'Jan Tester',
            'email' => 'webhook@test.nl',
            'telefoon' => '0612345678',
            'straatnaam' => 'Webhookstraat',
            'postcode' => '5678CD',
            'plaats' => 'Rotterdam',
            'omschrijving' => 'Test webhook omschrijving',
        ];

        $request = Request::create('/api/webhooks/cf7', 'POST', $testData);
        $controller = new LeadWebhookController();
        
        try {
            $response = $controller->store($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info('✅ Webhook processed successfully');
                $this->line("   Lead ID: {$data['data']['lead_id']}");
                $this->line("   Company ID: {$data['data']['company_id']}");
                $this->line("   Company Existed: " . ($data['data']['company_existed'] ? 'YES' : 'NO'));
                
                // Verify company was created with correct fields
                $company = \App\Models\Company::find($data['data']['company_id']);
                $this->newLine();
                $this->info('Company fields verification:');
                $this->line("   Name: {$company->name}");
                $this->line("   Email: {$company->email}");
                $this->line("   Phone: {$company->phone}");
                $this->line("   Street: " . ($company->street ?? 'NULL'));
                $this->line("   House Number: " . ($company->house_number ?? 'NULL'));
                $this->line("   Postal Code: " . ($company->postal_code ?? 'NULL'));
                $this->line("   City: " . ($company->city ?? 'NULL'));
                
                // Verify lead
                $lead = \App\Models\Lead::find($data['data']['lead_id']);
                $this->newLine();
                $this->info('Lead fields verification:');
                $this->line("   Name: {$lead->name}");
                $this->line("   Email: {$lead->email}");
                $this->line("   Status: {$lead->status->value}");
                
                // Clean up
                if ($this->confirm('Delete test lead and company?', true)) {
                    $lead->delete();
                    $company->delete();
                    $this->info('✅ Test data deleted');
                }
            } else {
                $this->error('❌ Webhook failed: ' . $data['message']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        // Test 2: Minimal data (only email)
        $this->newLine();
        $this->info('Test 2: Minimal webhook data (only email)');
        
        $minimalData = [
            'email' => 'minimal@test.nl',
        ];

        $request = Request::create('/api/webhooks/cf7', 'POST', $minimalData);
        
        try {
            $response = $controller->store($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info('✅ Webhook processed successfully with minimal data');
                
                $company = \App\Models\Company::find($data['data']['company_id']);
                $this->line("   Company Name: {$company->name}");
                $this->line("   Email: {$company->email}");
                $this->line("   Street: " . ($company->street ?? 'NULL'));
                
                // Clean up
                $lead = \App\Models\Lead::find($data['data']['lead_id']);
                $lead->delete();
                $company->delete();
                $this->info('✅ Test data deleted');
            } else {
                $this->error('❌ Webhook failed: ' . $data['message']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Error with minimal data: ' . $e->getMessage());
        }

        // Test 3: Postcode lookup (only postcode + house number, no street/city)
        $this->newLine();
        $this->info('Test 3: Postcode lookup (only postcode + huisnummer)');
        
        $postcodeData = [
            'email' => 'postcodetest@test.nl',
            'bedrijfsnaam' => 'Postcode Test BV',
            'naam' => 'Postcode Tester',
            'telefoon' => '0612345999',
            'postcode' => '7627PN',
            'huisnummer' => '6',
            // NO straatnaam or plaats - should be auto-filled via postcode lookup
        ];

        $request = Request::create('/api/webhooks/cf7', 'POST', $postcodeData);
        
        try {
            $response = $controller->store($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info('✅ Webhook processed successfully');
                
                $company = \App\Models\Company::find($data['data']['company_id']);
                $lead = \App\Models\Lead::find($data['data']['lead_id']);
                
                $this->newLine();
                $this->info('Postcode lookup verification:');
                $this->line("   Postal Code: {$company->postal_code}");
                $this->line("   House Number: {$company->house_number}");
                $this->line("   Street (auto-filled): " . ($company->street ?? 'NULL'));
                $this->line("   City (auto-filled): " . ($company->city ?? 'NULL'));
                
                // Verify street and city were auto-filled correctly
                if ($company->street === 'Zuivelhof' && $company->city === 'Bornerbroek') {
                    $this->info('   ✅ Street and city correctly auto-filled from postcode lookup!');
                } elseif ($company->street && $company->city) {
                    $this->warn("   ⚠️  Street/city filled but unexpected: {$company->street}, {$company->city}");
                } else {
                    $this->error('   ❌ Street or city NOT auto-filled (lookup may have failed)');
                }
                
                // Verify lead also has correct data
                $this->newLine();
                $this->info('Lead address verification:');
                $this->line("   Lead Street: " . ($lead->street ?? 'NULL'));
                $this->line("   Lead House Number: " . ($lead->house_number ?? 'NULL'));
                $this->line("   Lead City: " . ($lead->city ?? 'NULL'));
                
                // Clean up
                if ($this->confirm('Delete test lead and company?', true)) {
                    $lead->delete();
                    $company->delete();
                    $this->info('✅ Test data deleted');
                }
            } else {
                $this->error('❌ Webhook failed: ' . $data['message']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Error with postcode lookup test: ' . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
        }

        // Test 4: Street + house number combined (like from website: "Zuivelhof 6")
        $this->newLine();
        $this->info('Test 4: Combined street+number field (simulates website form)');
        
        $websiteData = [
            'emailadres' => 'website@test.nl',
            'naam' => 'Website Tester',
            'telefoonnummer' => '0612345777',
            'straatnaam' => 'Zuivelhof 6',  // COMBINED street + house number!
            'postcode' => '7627PN',
            // NO plaats - should be auto-filled via postcode lookup
        ];

        $request = Request::create('/api/webhooks/cf7', 'POST', $websiteData);
        
        try {
            $response = $controller->store($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info('✅ Webhook processed successfully');
                
                $company = \App\Models\Company::find($data['data']['company_id']);
                $lead = \App\Models\Lead::find($data['data']['lead_id']);
                
                $this->newLine();
                $this->info('Street/number split verification:');
                $this->line("   Original straatnaam: Zuivelhof 6");
                $this->line("   Split Street: " . ($company->street ?? 'NULL'));
                $this->line("   Split House Number: " . ($company->house_number ?? 'NULL'));
                $this->line("   Postal Code: {$company->postal_code}");
                $this->line("   City (auto-filled): " . ($company->city ?? 'NULL'));
                
                // Verify split and auto-fill worked correctly
                $success = true;
                if ($company->street !== 'Zuivelhof') {
                    $this->error("   ❌ Street not split correctly: expected 'Zuivelhof', got '{$company->street}'");
                    $success = false;
                }
                if ($company->house_number !== '6') {
                    $this->error("   ❌ House number not split correctly: expected '6', got '{$company->house_number}'");
                    $success = false;
                }
                if ($company->city !== 'Bornerbroek') {
                    $this->error("   ❌ City not auto-filled correctly: expected 'Bornerbroek', got '{$company->city}'");
                    $success = false;
                }
                
                if ($success) {
                    $this->info('   ✅ Street/number split AND city auto-fill working perfectly!');
                }
                
                // Clean up
                if ($this->confirm('Delete test lead and company?', true)) {
                    $lead->delete();
                    $company->delete();
                    $this->info('✅ Test data deleted');
                }
            } else {
                $this->error('❌ Webhook failed: ' . $data['message']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Error with combined street test: ' . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
        }

        $this->newLine();
        $this->info('✅ All webhook tests completed');
        
        return 0;
    }
}
