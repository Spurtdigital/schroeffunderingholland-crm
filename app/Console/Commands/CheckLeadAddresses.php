<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;

class CheckLeadAddresses extends Command
{
    protected $signature = 'check:lead-addresses {--limit=20}';
    protected $description = 'Check lead addresses for completeness';

    public function handle()
    {
        $limit = $this->option('limit');
        $this->info("=== Checking Last {$limit} Leads ===");
        $this->newLine();
        
        $leads = Lead::with('company')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
        
        if ($leads->isEmpty()) {
            $this->warn('No leads found');
            return 0;
        }
        
        $incomplete = 0;
        
        foreach ($leads as $lead) {
            $this->line("Lead #{$lead->id} - {$lead->name} ({$lead->email})");
            $this->line("  Created: {$lead->created_at->format('Y-m-d H:i')}");
            
            // Check lead address
            $leadComplete = $lead->street && $lead->house_number && $lead->postal_code && $lead->city;
            $this->line("  Lead Address: " . 
                ($lead->street ?? '❌') . ' ' . 
                ($lead->house_number ?? '❌') . ', ' . 
                ($lead->postal_code ?? '❌') . ' ' . 
                ($lead->city ?? '❌')
            );
            
            // Check company address
            $company = $lead->company;
            $companyComplete = $company->street && $company->house_number && $company->postal_code && $company->city;
            $this->line("  Company Address: " . 
                ($company->street ?? '❌') . ' ' . 
                ($company->house_number ?? '❌') . ', ' . 
                ($company->postal_code ?? '❌') . ' ' . 
                ($company->city ?? '❌')
            );
            
            if (!$leadComplete || !$companyComplete) {
                $this->warn("  ⚠️  INCOMPLETE ADDRESS");
                $incomplete++;
                
                // Show request data if available
                if ($lead->request_data) {
                    $this->line("  Original data: " . json_encode($lead->request_data, JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->info("  ✅ Complete");
            }
            
            $this->newLine();
        }
        
        $complete = $leads->count() - $incomplete;
        $this->newLine();
        $this->info("Summary:");
        $this->line("  Complete: {$complete}");
        $this->line("  Incomplete: {$incomplete}");
        
        return 0;
    }
}
