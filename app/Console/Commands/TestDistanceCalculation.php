<?php

namespace App\Console\Commands;

use App\Services\DistanceCalculatorService;
use Illuminate\Console\Command;

class TestDistanceCalculation extends Command
{
    protected $signature = 'test:distance {destination?}';
    protected $description = 'Test Google Maps distance calculation';

    public function handle(DistanceCalculatorService $distanceService)
    {
        $this->info('=== Test Distance Calculation ===');
        $this->newLine();
        
        if (!$distanceService->isAvailable()) {
            $this->error('❌ Distance service not available');
            $this->warn('   Make sure Google Maps API key and company address are configured in Settings');
            return 1;
        }
        
        $this->info('✅ Distance service is available');
        $this->newLine();
        
        // Use provided destination or default test address
        $destination = $this->argument('destination') ?? 'Dam 1, 1012 JS Amsterdam';
        
        $this->info("Calculating distance to: {$destination}");
        $this->line('From company address...');
        $this->newLine();
        
        $result = $distanceService->calculateDistance($destination);
        
        if ($result['success']) {
            $this->info('✅ Distance calculated successfully!');
            $this->newLine();
            $this->line("Distance: {$result['distance_text']} ({$result['distance_km']} km)");
            $this->line("Duration: {$result['duration_text']}");
            $this->newLine();
            
            // Calculate travel costs
            $settings = app(\App\Settings\GeneralSettings::class);
            $costPerKm = $settings->default_travel_cost_per_km ?? 0.23;
            $totalCost = $result['distance_km'] * $costPerKm;
            
            $this->info("Travel cost calculation:");
            $this->line("  {$result['distance_km']} km × €" . number_format($costPerKm, 2) . " = €" . number_format($totalCost, 2));
        } else {
            $this->error('❌ Failed to calculate distance');
            $this->warn('   Error: ' . ($result['error'] ?? 'Unknown error'));
            $this->warn('   Check the logs for more details');
            return 1;
        }
        
        return 0;
    }
}
