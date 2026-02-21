<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\MoneybirdService;
use Illuminate\Console\Command;

class TestMoneybirdQuote extends Command
{
    protected $signature = 'test:moneybird-quote {quote_number}';
    protected $description = 'Test sending a quote to Moneybird';

    public function handle(MoneybirdService $moneybirdService): int
    {
        $quoteNumber = $this->argument('quote_number');
        
        $quote = Quote::where('quote_number', $quoteNumber)->first();
        
        if (!$quote) {
            $this->error("Quote {$quoteNumber} niet gevonden!");
            return 1;
        }
        
        $this->info("Quote gevonden: {$quote->quote_number}");
        $this->line("Status: {$quote->status->value} ({$quote->status->getLabel()})");
        $this->line("Moneybird ID: " . ($quote->moneybird_quote_id ?? 'GEEN'));
        $this->line("Aantal items: {$quote->items()->count()}");
        
        if ($quote->company) {
            $this->line("Company: {$quote->company->name}");
            $this->line("Company Moneybird ID: " . ($quote->company->moneybird_contact_id ?? 'GEEN'));
        } else {
            $this->error("Geen company gekoppeld!");
            return 1;
        }
        
        $this->newLine();
        
        // List items
        if ($quote->items()->count() > 0) {
            $this->info("Items:");
            foreach ($quote->items as $item) {
                $this->line("  - {$item->description} ({$item->quantity} × €{$item->unit_price} = €{$item->total})");
            }
            $this->newLine();
        }
        
        // Check Moneybird config
        if (!$moneybirdService->isConfigured()) {
            $this->error("Moneybird is niet geconfigureerd!");
            return 1;
        }
        
        $this->info("Moneybird is geconfigureerd ✓");
        
        // Ask to send
        if ($this->confirm('Wil je deze quote naar Moneybird versturen?', true)) {
            try {
                $this->info('Versturen naar Moneybird...');
                $moneybirdService->sendQuote($quote);
                $this->info("✅ Quote succesvol verstuurd naar Moneybird!");
                $this->line("Moneybird ID: {$quote->fresh()->moneybird_quote_id}");
            } catch (\Exception $e) {
                $this->error("❌ Fout: " . $e->getMessage());
                $this->line("Stack trace:");
                $this->line($e->getTraceAsString());
                return 1;
            }
        }
        
        return 0;
    }
}
