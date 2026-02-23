<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Actions\ApproveQuoteAction;
use App\Filament\Resources\Quotes\Actions\ConvertToProjectAction;
use App\Filament\Resources\Quotes\Actions\SendToMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\SyncFromMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\UpdateMoneybirdAction;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Services\QuoteCalculationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendToMoneybirdAction::make(),
            UpdateMoneybirdAction::make(),
            SyncFromMoneybirdAction::make(),
            ApproveQuoteAction::make(),
            ConvertToProjectAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $quote = $this->getRecord();
        app(QuoteCalculationService::class)->calculateTotals($quote);

        // Sync manually selected aannames
        $assumptionIds = $this->data['assumption_ids'] ?? [];
        $quote->assumptions()->sync($assumptionIds);

        // Auto-add SKU-triggered assumptions not already selected
        $this->autoSyncSkuAssumptions($quote);
    }

    /**
     * Check quote items for SKU matches and auto-add triggered assumptions.
     */
    protected function autoSyncSkuAssumptions(Quote $quote): void
    {
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
