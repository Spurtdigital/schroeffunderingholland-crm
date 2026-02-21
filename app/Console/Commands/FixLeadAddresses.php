<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Company;
use App\Services\PostcodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixLeadAddresses extends Command
{
    protected $signature = 'fix:lead-addresses {--dry-run : Show what would be fixed without actually fixing}';
    protected $description = 'Fix incomplete lead addresses using postcode lookup';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }
        
        $this->info('=== Fixing Incomplete Lead Addresses ===');
        $this->newLine();
        
        // Find leads with incomplete addresses
        $leads = Lead::with('company')
            ->where(function($query) {
                $query->whereNull('house_number')
                      ->orWhereNull('city');
            })
            ->get();
        
        if ($leads->isEmpty()) {
            $this->info('No incomplete leads found!');
            return 0;
        }
        
        $this->info("Found {$leads->count()} leads with incomplete addresses");
        $this->newLine();
        
        $postcodeService = app(PostcodeService::class);
        $fixed = 0;
        $failed = 0;
        
        foreach ($leads as $lead) {
            $this->line("Processing Lead #{$lead->id} - {$lead->name}");
            
            $needsUpdate = false;
            $updates = ['lead' => [], 'company' => []];
            
            // Try to split street + house number if combined
            if ($lead->street && !$lead->house_number) {
                if (preg_match('/^(.+?)\s+(\d+[a-zA-Z]?(?:\s*-\s*\d+[a-zA-Z]?)?)$/', trim($lead->street), $matches)) {
                    $street = trim($matches[1]);
                    $houseNumber = trim($matches[2]);
                    
                    $updates['lead']['street'] = $street;
                    $updates['lead']['house_number'] = $houseNumber;
                    $needsUpdate = true;
                    
                    $this->line("  Split: '{$lead->street}' → street: '{$street}', number: '{$houseNumber}'");
                }
            }
            
            // Determine what to use for lookup
            $street = $updates['lead']['street'] ?? $lead->street;
            $houseNumber = $updates['lead']['house_number'] ?? $lead->house_number;
            $postalCode = $lead->postal_code;
            
            // Try postcode lookup if we have postal code + house number but missing city
            if ($postalCode && $houseNumber && !$lead->city) {
                $this->line("  Looking up postcode: {$postalCode} + {$houseNumber}");
                
                $result = $postcodeService->lookup($postalCode, $houseNumber);
                
                if ($result) {
                    // Update lead city if missing
                    if (!$lead->city) {
                        $updates['lead']['city'] = $result['city'];
                        $needsUpdate = true;
                    }
                    
                    // Update lead street if we got it from lookup and current is missing
                    if (!$street && $result['street']) {
                        $updates['lead']['street'] = $result['street'];
                        $needsUpdate = true;
                    }
                    
                    $this->info("  ✅ Found: {$result['street']}, {$result['city']}");
                } else {
                    $this->warn("  ⚠️  Postcode lookup failed");
                }
            }
            
            // Fix company address too
            $company = $lead->company;
            if ($company) {
                // Split company street if needed
                if ($company->street && !$company->house_number) {
                    if (preg_match('/^(.+?)\s+(\d+[a-zA-Z]?(?:\s*-\s*\d+[a-zA-Z]?)?)$/', trim($company->street), $matches)) {
                        $updates['company']['street'] = trim($matches[1]);
                        $updates['company']['house_number'] = trim($matches[2]);
                    }
                }
                
                // Copy from lead updates if company is missing data
                $companyStreet = $updates['company']['street'] ?? $company->street;
                $companyHouseNumber = $updates['company']['house_number'] ?? $company->house_number;
                $companyPostalCode = $company->postal_code;
                
                // Use lead data if company is missing
                if (!$companyStreet && isset($updates['lead']['street'])) {
                    $updates['company']['street'] = $updates['lead']['street'];
                }
                if (!$companyHouseNumber && isset($updates['lead']['house_number'])) {
                    $updates['company']['house_number'] = $updates['lead']['house_number'];
                }
                
                // Lookup city for company if missing
                if ($companyPostalCode && ($updates['company']['house_number'] ?? $companyHouseNumber) && !$company->city) {
                    $result = $postcodeService->lookup($companyPostalCode, $updates['company']['house_number'] ?? $companyHouseNumber);
                    if ($result && !$company->city) {
                        $updates['company']['city'] = $result['city'];
                    }
                }
            }
            
            // Apply updates
            if (!$dryRun && !empty($updates['lead'])) {
                $lead->update($updates['lead']);
                $this->info("  Updated lead: " . json_encode($updates['lead']));
            } elseif ($dryRun && !empty($updates['lead'])) {
                $this->line("  Would update lead: " . json_encode($updates['lead']));
            }
            
            if (!$dryRun && !empty($updates['company'])) {
                $company->update($updates['company']);
                $this->info("  Updated company: " . json_encode($updates['company']));
            } elseif ($dryRun && !empty($updates['company'])) {
                $this->line("  Would update company: " . json_encode($updates['company']));
            }
            
            if (!empty($updates['lead']) || !empty($updates['company'])) {
                $fixed++;
            } else {
                $this->warn("  ❌ Could not fix this lead");
                $failed++;
            }
            
            $this->newLine();
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->line("  Fixed: {$fixed}");
        $this->line("  Failed: {$failed}");
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN MODE - Run without --dry-run to apply changes');
        }
        
        return 0;
    }
}
