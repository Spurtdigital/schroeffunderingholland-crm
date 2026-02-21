<?php

namespace App\Services;

use App\Models\Quote;

class QuoteCalculationService
{
    /**
     * Calculate and update all totals for a quote based on its items
     * 
     * @param Quote $quote
     * @param float $vatRate VAT rate as percentage (default 21%)
     */
    public function calculateTotals(Quote $quote, float $vatRate = 21.0): void
    {
        // Get all quote items
        $items = $quote->items;

        // Calculate subtotal from all items (including mileage items)
        $subtotal = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        // Calculate VAT amount
        $vatAmount = round($subtotal * ($vatRate / 100), 2);

        // Calculate total including VAT
        $total = $subtotal + $vatAmount;

        // Update the quote without triggering observers (to prevent recursion)
        $quote->updateQuietly([
            'subtotal' => round($subtotal, 2),
            'vat_amount' => $vatAmount,
            'total' => round($total, 2),
        ]);
    }

    /**
     * Recalculate totals for a quote (convenience method)
     */
    public function recalculate(Quote $quote, float $vatRate = 21.0): void
    {
        $this->calculateTotals($quote, $vatRate);
    }
}
