<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\QuoteCalculationService;
use Illuminate\Console\Command;

class RecalculateQuoteTotals extends Command
{
    protected $signature = 'recalculate:quote-totals {quote_number?} {--all}';
    protected $description = 'Recalculate quote totals';

    public function handle(QuoteCalculationService $calculationService): int
    {
        if ($this->option('all')) {
            $quotes = Quote::all();
            $this->info("Herberekenen van {$quotes->count()} quotes...");
        } elseif ($quoteNumber = $this->argument('quote_number')) {
            $quotes = Quote::where('quote_number', $quoteNumber)->get();
            if ($quotes->isEmpty()) {
                $this->error('Quote niet gevonden!');
                return 1;
            }
        } else {
            $this->error('Geef een quote nummer op of gebruik --all');
            return 1;
        }
        
        foreach ($quotes as $quote) {
            $oldTotal = $quote->total;
            $calculationService->calculateTotals($quote);
            $quote->refresh();
            
            $this->line("{$quote->quote_number}: €{$oldTotal} → €{$quote->total}");
        }
        
        $this->newLine();
        $this->info('✅ Klaar!');
        
        return 0;
    }
}
