<?php

namespace App\Console\Commands;

use App\Services\MoneybirdService;
use Illuminate\Console\Command;

class InspectMoneybirdEstimate extends Command
{
    protected $signature = 'inspect:moneybird-estimate {estimate_id}';
    protected $description = 'Inspect a Moneybird estimate';

    public function handle(MoneybirdService $moneybirdService): int
    {
        $estimateId = $this->argument('estimate_id');
        
        if (!$moneybirdService->isConfigured()) {
            $this->error('Moneybird is niet geconfigureerd!');
            return 1;
        }
        
        try {
            $moneybird = $moneybirdService->getMoneybirdInstance();
            $estimate = $moneybird->estimate()->find($estimateId);
            
            $this->info("Estimate #{$estimate->estimate_id}");
            $this->line("State: {$estimate->state}");
            $this->line("Reference: {$estimate->reference}");
            $this->line("Contact ID: {$estimate->contact_id}");
            $this->newLine();
            
            $this->info("All attributes:");
            $attrs = $estimate->attributes();
            foreach ($attrs as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }
                $this->line("  {$key}: {$value}");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
