<?php

namespace App\Observers;

use App\Enums\LeadStatus;
use App\Enums\QuoteStatus;
use App\Enums\StockReservationStatus;
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
     * Handle the Quote "deleting" event.
     * Release all active stock reservations when a quote is deleted.
     */
    public function deleting(Quote $quote): void
    {
        foreach ($quote->stockReservations()->where('status', StockReservationStatus::Active)->get() as $reservation) {
            $reservation->release();
        }
    }

    /**
     * Handle the Quote "updating" event.
     * - Geeft voorraad vrij bij afwijzing
     * - Synchroniseert de lead-status op basis van de nieuwe quote-status
     */
    public function updating(Quote $quote): void
    {
        if (! $quote->isDirty('status')) {
            return;
        }

        $newStatus = $quote->status;

        // Voorraad vrijgeven bij afwijzing
        if ($newStatus === QuoteStatus::Rejected) {
            foreach ($quote->stockReservations()->where('status', 'active')->get() as $reservation) {
                $reservation->release();
            }
        }

        // Lead-status synchroniseren
        $this->syncLeadStatus($quote, $newStatus);
    }

    /**
     * Bepaal de juiste lead-status op basis van de nieuwe quote-status en
     * pas de gekoppelde lead aan als die bestaat.
     *
     * Koppeling loopt via quote->lead (quote.lead_id) of via de lead die
     * converted_to_quote_id = quote.id heeft.
     */
    private function syncLeadStatus(Quote $quote, QuoteStatus $newQuoteStatus): void
    {
        // Zoek de gekoppelde lead — via lead_id op de quote
        $lead = $quote->lead ?? null;

        // Fallback: zoek een lead die deze quote als converted_to_quote_id heeft
        if (! $lead) {
            $lead = \App\Models\Lead::where('converted_to_quote_id', $quote->id)->first();
        }

        if (! $lead) {
            return;
        }

        $newLeadStatus = match ($newQuoteStatus) {
            // Offerte verstuurd of klaargezet → lead staat op "offerte gemaakt"
            QuoteStatus::Sent,
            QuoteStatus::MoneybirdPrepared,
            QuoteStatus::Approved  => LeadStatus::Quoted,

            // Offerte omgezet naar project → lead geconverteerd
            QuoteStatus::Converted => LeadStatus::Converted,

            // Offerte afgewezen → lead afgewezen, maar alleen als er
            // geen andere nog-actieve quotes zijn voor dezelfde lead
            QuoteStatus::Rejected  => $this->resolveRejectedLeadStatus($lead, $quote),

            // Draft: geen wijziging aan lead-status
            default                => null,
        };

        if ($newLeadStatus === null) {
            return;
        }

        // Niet terugzetten naar een "lagere" status als de lead al geconverteerd is
        if ($lead->status === LeadStatus::Converted && $newLeadStatus !== LeadStatus::Converted) {
            return;
        }

        if ($lead->status === $newLeadStatus) {
            return;
        }

        $lead->update(['status' => $newLeadStatus]);
    }

    /**
     * Als de offerte wordt afgewezen: controleer of er nog andere actieve
     * offertes zijn voor dezelfde lead. Zo ja, laat de lead-status ongewijzigd.
     * Zo nee, zet de lead op "afgewezen".
     */
    private function resolveRejectedLeadStatus(\App\Models\Lead $lead, Quote $rejectedQuote): ?LeadStatus
    {
        $activeStatuses = [
            QuoteStatus::Draft->value,
            QuoteStatus::Sent->value,
            QuoteStatus::MoneybirdPrepared->value,
            QuoteStatus::Approved->value,
        ];

        $hasOtherActiveQuotes = \App\Models\Quote::where('lead_id', $lead->id)
            ->where('id', '!=', $rejectedQuote->id)
            ->whereIn('status', $activeStatuses)
            ->exists();

        if ($hasOtherActiveQuotes) {
            return null; // Laat lead-status ongemoeid
        }

        return LeadStatus::Rejected;
    }
}
