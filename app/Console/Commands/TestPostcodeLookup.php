<?php

namespace App\Console\Commands;

use App\Services\PostcodeService;
use Illuminate\Console\Command;

class TestPostcodeLookup extends Command
{
    protected $signature = 'test:postcode-lookup {postal_code?} {house_number?}';
    protected $description = 'Test postcode lookup service';

    public function handle()
    {
        $this->info('=== Test Postcode Lookup Service ===');
        
        $postcodeService = app(PostcodeService::class);
        
        // Test cases
        $testCases = [
            ['7627PN', '6', 'Expected: Zuivelhof, Enschede'],
            ['1012JS', '1', 'Expected: Dam, Amsterdam'],
            ['3511EP', '1A', 'Expected: Vredenburg, Utrecht'],
        ];

        // If arguments provided, test those
        if ($this->argument('postal_code') && $this->argument('house_number')) {
            $testCases = [[
                $this->argument('postal_code'),
                $this->argument('house_number'),
                'User provided'
            ]];
        }

        foreach ($testCases as $index => $test) {
            [$postalCode, $houseNumber, $description] = $test;
            
            $this->newLine();
            $this->info("Test " . ($index + 1) . ": {$description}");
            $this->line("  Postcode: {$postalCode}");
            $this->line("  Huisnummer: {$houseNumber}");
            
            $result = $postcodeService->lookup($postalCode, $houseNumber);
            
            if ($result) {
                $this->info("  ✅ SUCCESS");
                $this->line("  Straat: {$result['street']}");
                $this->line("  Plaats: {$result['city']}");
                $this->line("  Gemeente: {$result['municipality']}");
                $this->line("  Provincie: {$result['province']}");
            } else {
                $this->error("  ❌ FAILED: Geen adres gevonden");
            }
        }

        // Test validation
        $this->newLine();
        $this->info('=== Test Postal Code Validation ===');
        
        $validationTests = [
            '1234AB' => true,
            '1234 AB' => true,  // Should be cleaned and validated
            '1234ab' => true,   // Should be uppercased
            '12345' => false,   // Too short
            'ABCD12' => false,  // Wrong format
            '' => false,
        ];

        foreach ($validationTests as $code => $expected) {
            $isValid = $postcodeService->isValidPostalCode($code);
            $status = $isValid === $expected ? '✅' : '❌';
            $this->line("{$status} '{$code}' - Valid: " . ($isValid ? 'YES' : 'NO') . " (Expected: " . ($expected ? 'YES' : 'NO') . ")");
        }

        $this->newLine();
        $this->info('✅ All tests completed');
        
        return 0;
    }
}
