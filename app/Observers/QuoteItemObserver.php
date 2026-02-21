<?php

namespace App\Observers;

use App\Models\QuoteItem;
use App\Services\QuoteCalculationService;

class QuoteItemObserver
{
    protected QuoteCalculationService $calculationService;

    public function __construct(QuoteCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Handle the QuoteItem "created" event.
     */
    public function created(QuoteItem $quoteItem): void
    {
        // Recalculate quote totals when a new item is added
        if ($quoteItem->quote) {
            $this->calculationService->calculateTotals($quoteItem->quote);
        }
    }

    /**
     * Handle the QuoteItem "updated" event.
     */
    public function updated(QuoteItem $quoteItem): void
    {
        // Recalculate quote totals when item quantity or price changes
        if ($quoteItem->wasChanged(['quantity', 'unit_price'])) {
            if ($quoteItem->quote) {
                $this->calculationService->calculateTotals($quoteItem->quote);
            }
        }
    }

    /**
     * Handle the QuoteItem "deleted" event.
     */
    public function deleted(QuoteItem $quoteItem): void
    {
        // Recalculate quote totals when an item is removed
        if ($quoteItem->quote) {
            $this->calculationService->calculateTotals($quoteItem->quote);
        }
    }
}
