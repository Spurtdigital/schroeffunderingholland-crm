<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\QuoteCalculationService;
use Illuminate\Console\Command;

class RecalculateQuotes extends Command
{
    protected $signature = 'quotes:recalculate {quote_number?}';
    protected $description = 'Recalculate quote totals';

    public function handle(QuoteCalculationService $service)
    {
        $quoteNumber = $this->argument('quote_number');

        if ($quoteNumber) {
            $quote = Quote::where('quote_number', $quoteNumber)->first();
            if (!$quote) {
                $this->error("Quote {$quoteNumber} not found!");
                return Command::FAILURE;
            }
            $quotes = [$quote];
        } else {
            $quotes = Quote::all();
        }

        foreach ($quotes as $quote) {
            $this->info("Recalculating {$quote->quote_number}...");
            $service->calculateTotals($quote);
            $quote->refresh();
            $this->line("  Subtotal: €{$quote->subtotal}");
            $this->line("  VAT: €{$quote->vat_amount}");
            $this->line("  Total: €{$quote->total}");
        }

        $this->info('Done!');
        return Command::SUCCESS;
    }
}
