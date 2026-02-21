<?php

namespace App\Console\Commands;

use App\Models\Quote;
use Illuminate\Console\Command;

class CheckQuoteTotals extends Command
{
    protected $signature = 'check:quote-totals {quote_number}';
    protected $description = 'Check quote totals';

    public function handle(): int
    {
        $quote = Quote::where('quote_number', $this->argument('quote_number'))->first();
        
        if (!$quote) {
            $this->error('Quote niet gevonden!');
            return 1;
        }
        
        $this->info("Quote: {$quote->quote_number}");
        $this->line("Subtotal: €{$quote->subtotal}");
        $this->line("VAT: €{$quote->vat_amount}");
        $this->line("Total: €{$quote->total}");
        $this->line("Items: {$quote->items->count()}");
        
        $this->newLine();
        $this->info("Items:");
        foreach ($quote->items as $item) {
            $this->line("  {$item->description}: {$item->quantity} × €{$item->unit_price} = €{$item->total}");
        }
        
        $calculatedSubtotal = $quote->items->sum('total');
        $this->newLine();
        $this->info("Berekend subtotal: €{$calculatedSubtotal}");
        
        if ($quote->subtotal != $calculatedSubtotal) {
            $this->error("⚠️ Subtotal klopt niet! Database: €{$quote->subtotal}, Berekend: €{$calculatedSubtotal}");
        }
        
        return 0;
    }
}
