<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\MoneybirdService;
use Illuminate\Console\Command;

class SyncMoneybirdQuoteStatuses extends Command
{
    protected $signature = 'sync:moneybird-quotes {--all : Sync all quotes} {quote_number? : Specific quote number}';
    protected $description = 'Sync quote statuses from Moneybird back to CRM (2-way sync)';

    public function handle(MoneybirdService $moneybirdService): int
    {
        if (!$moneybirdService->isConfigured()) {
            $this->error('Moneybird is niet geconfigureerd!');
            return 1;
        }
        
        // Get quotes to sync
        $quotes = $this->getQuotesToSync();
        
        if ($quotes->isEmpty()) {
            $this->warn('Geen quotes gevonden om te synchroniseren.');
            return 0;
        }
        
        $this->info("Synchroniseren van {$quotes->count()} quote(s)...");
        $this->newLine();
        
        $moneybird = $moneybirdService->getMoneybirdInstance();
        $synced = 0;
        $errors = 0;
        
        foreach ($quotes as $quote) {
            try {
                $this->line("Checking {$quote->quote_number}...");
                
                if (!$quote->moneybird_quote_id) {
                    $this->line("  ⊘ Nog niet verstuurd naar Moneybird");
                    continue;
                }
                
                // Fetch estimate from Moneybird
                $estimate = $moneybird->estimate()->find($quote->moneybird_quote_id);
                
                // Map Moneybird state to CRM status
                $newStatus = $this->mapMoneybirdStateToCrmStatus($estimate);
                
                // Check if quote_number needs updating (CONCEPT → Moneybird estimate number)
                $quoteNumberNeedsUpdate = $estimate->estimate_id
                    && str_starts_with($quote->quote_number ?? '', 'CONCEPT-');
                
                if (!$newStatus && !$quoteNumberNeedsUpdate) {
                    $this->line("  - Geen status wijziging nodig (Moneybird: {$estimate->state})");
                    continue;
                }
                
                if ($quote->status === $newStatus && !$quoteNumberNeedsUpdate) {
                    $this->line("  ✓ Status al correct: {$newStatus->getLabel()}");
                    continue;
                }
                
                // Prepare update data
                $updateData = [];
                
                if ($newStatus) {
                    $updateData['status'] = $newStatus;
                }
                
                // Set timestamp fields based on status
                if ($newStatus === QuoteStatus::Sent && !$quote->sent_at && $estimate->sent_at) {
                    $updateData['sent_at'] = \Carbon\Carbon::parse($estimate->sent_at);
                }
                
                if ($newStatus === QuoteStatus::Approved && !$quote->approved_at && $estimate->accepted_at) {
                    $updateData['approved_at'] = \Carbon\Carbon::parse($estimate->accepted_at);
                }
                
                // Replace CONCEPT-number with Moneybird estimate number if still a placeholder
                if ($quoteNumberNeedsUpdate) {
                    $updateData['quote_number'] = $estimate->estimate_id;
                }
                
                // Update quote status
                $oldStatus = $quote->status;
                $quote->update($updateData);
                
                $parts = [];
                if ($newStatus && $newStatus !== $oldStatus) {
                    $parts[] = "{$oldStatus->getLabel()} → {$newStatus->getLabel()}";
                }
                if ($quoteNumberNeedsUpdate) {
                    $parts[] = "offertenummer → {$estimate->estimate_id}";
                }
                $this->info("  ✅ Bijgewerkt: " . implode(', ', $parts));
                $synced++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Fout bij {$quote->quote_number}: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("Klaar! {$synced} quote(s) gesynchroniseerd, {$errors} fout(en).");
        
        return $errors > 0 ? 1 : 0;
    }
    
    protected function getQuotesToSync()
    {
        $quoteNumber = $this->argument('quote_number');
        
        if ($quoteNumber) {
            return Quote::where('quote_number', $quoteNumber)->get();
        }
        
        if ($this->option('all')) {
            // Only sync quotes that have been sent to Moneybird
            return Quote::whereNotNull('moneybird_quote_id')->get();
        }
        
        // Default: sync recent quotes (last 30 days)
        return Quote::whereNotNull('moneybird_quote_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();
    }
    
    protected function mapMoneybirdStateToCrmStatus($estimate): ?QuoteStatus
    {
        // Moneybird estimate states to CRM statuses
        // Priority: rejected > accepted > sent > draft
        
        if ($estimate->rejected_at) {
            return QuoteStatus::Rejected;
        }
        
        if ($estimate->accepted_at) {
            return QuoteStatus::Approved;
        }
        
        if ($estimate->sent_at) {
            return QuoteStatus::Sent;
        }
        
        if ($estimate->state === 'draft') {
            // Keep current CRM status if still draft in Moneybird
            return null;
        }
        
        return null;
    }
}
