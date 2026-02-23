<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\QuoteAssumption;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function afterCreate(): void
    {
        $quote = $this->getRecord();

        // Sync manually selected aannames
        $assumptionIds = $this->data['assumption_ids'] ?? [];
        $quote->assumptions()->sync($assumptionIds);

        // Auto-add SKU-triggered assumptions not already selected
        $this->autoSyncSkuAssumptions($quote);
    }

    /**
     * Check quote items for SKU matches and auto-add triggered assumptions.
     */
    protected function autoSyncSkuAssumptions(\App\Models\Quote $quote): void
    {
        // Collect all SKUs from product items in this quote
        $skus = $quote->items()
            ->with('itemable')
            ->get()
            ->filter(fn ($item) => $item->itemable && method_exists($item->itemable, 'getAttribute'))
            ->map(fn ($item) => strtoupper((string) $item->itemable->sku))
            ->filter()
            ->unique()
            ->values();

        if ($skus->isEmpty()) {
            return;
        }

        // Find all active assumptions with a trigger_sku that matches any product SKU
        $triggered = QuoteAssumption::where('active', true)
            ->whereNotNull('trigger_sku')
            ->get()
            ->filter(function (QuoteAssumption $assumption) use ($skus) {
                $trigger = strtoupper(trim($assumption->trigger_sku));
                return $skus->contains(fn ($sku) => str_contains($sku, $trigger));
            })
            ->pluck('id');

        if ($triggered->isNotEmpty()) {
            $quote->assumptions()->syncWithoutDetaching($triggered->toArray());
        }
    }
}
