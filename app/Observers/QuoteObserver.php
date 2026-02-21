<?php

namespace App\Observers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\QuoteCalculationService;

class QuoteObserver
{
    protected QuoteCalculationService $calculationService;

    public function __construct(QuoteCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Handle the Quote "created" event.
     */
    public function created(Quote $quote): void
    {
        // Calculate totals after quote is created (if it has items)
        if ($quote->items()->count() > 0) {
            $this->calculationService->calculateTotals($quote);
        }
    }

    /**
     * Handle the Quote "updating" event.
     * Release stock when quote is rejected
     */
    public function updating(Quote $quote): void
    {
        // Check if status is changing to Rejected
        if ($quote->isDirty('status')) {
            $newStatus = $quote->status;

            // If moving to Rejected, release all stock reservations
            if ($newStatus === QuoteStatus::Rejected) {
                // Release all active stock reservations for this quote
                foreach ($quote->stockReservations()->where('status', 'active')->get() as $reservation) {
                    $reservation->release();
                }
            }
        }
    }
}
