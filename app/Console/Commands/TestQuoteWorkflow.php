<?php

namespace App\Console\Commands;

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Console\Command;

class TestQuoteWorkflow extends Command
{
    protected $signature = 'test:quote-workflow';
    protected $description = 'Test the complete quote workflow with product selection';

    public function handle()
    {
        $this->info('=== Testing Quote Workflow ===');
        $this->newLine();

        // Get first quote
        $quote = Quote::first();
        if (!$quote) {
            $this->error('No quote found in database!');
            return Command::FAILURE;
        }

        $this->info("Found Quote #{$quote->quote_number}");
        $this->info("Current totals:");
        $this->line("  Subtotal: €{$quote->subtotal}");
        $this->line("  BTW: €{$quote->vat_amount}");
        $this->line("  Totaal: €{$quote->total}");
        $this->newLine();

        // Get a product
        $product = Product::where('sku', 'SP-001')->first();
        if (!$product) {
            $this->error('Product SP-001 not found!');
            return Command::FAILURE;
        }

        $this->info("Adding product: {$product->name}");
        $this->line("  Price: €{$product->price}");
        $this->line("  Quantity: 5");
        $this->newLine();

        // Add a quote item
        $item = QuoteItem::create([
            'quote_id' => $quote->id,
            'type' => QuoteItemType::Product,
            'itemable_type' => Product::class,
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => 5,
            'unit_price' => $product->price,
        ]);

        $this->info("Created QuoteItem #{$item->id}");
        $this->line("  Item total: €{$item->total}");
        $this->newLine();

        // Refresh quote to see updated totals
        $quote->refresh();

        $this->info("Quote totals AFTER adding item:");
        $this->line("  Subtotal: €{$quote->subtotal}");
        $this->line("  BTW (21%): €{$quote->vat_amount}");
        $this->line("  Totaal: €{$quote->total}");
        $this->newLine();

        // Verify calculation
        $expectedSubtotal = $quote->items->sum('total');
        $expectedVat = round($expectedSubtotal * 0.21, 2);
        $expectedTotal = $expectedSubtotal + $expectedVat;

        $this->info("Verification:");
        if ($quote->subtotal == $expectedSubtotal) {
            $this->line("  ✓ Subtotal is correct");
        } else {
            $this->error("  ✗ Subtotal mismatch! Expected: €{$expectedSubtotal}, Got: €{$quote->subtotal}");
        }

        if ($quote->vat_amount == $expectedVat) {
            $this->line("  ✓ VAT is correct");
        } else {
            $this->error("  ✗ VAT mismatch! Expected: €{$expectedVat}, Got: €{$quote->vat_amount}");
        }

        if ($quote->total == $expectedTotal) {
            $this->line("  ✓ Total is correct");
        } else {
            $this->error("  ✗ Total mismatch! Expected: €{$expectedTotal}, Got: €{$quote->total}");
        }

        $this->newLine();
        $this->info('All items in quote:');
        foreach ($quote->items as $quoteItem) {
            $this->line("  - {$quoteItem->description} (x{$quoteItem->quantity} @ €{$quoteItem->unit_price}) = €{$quoteItem->total}");
        }

        return Command::SUCCESS;
    }
}
