<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Actions\ApproveQuoteAction;
use App\Filament\Resources\Quotes\Actions\ConvertToProjectAction;
use App\Filament\Resources\Quotes\Actions\DuplicateQuoteAction;
use App\Filament\Resources\Quotes\Actions\ResendQuoteAction;
use App\Filament\Resources\Quotes\Actions\SendQuoteAction;
use App\Filament\Resources\Quotes\Actions\SendToMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\SyncFromMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\UpdateMoneybirdAction;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Services\QuoteCalculationService;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hoofdacties
            ApproveQuoteAction::make(),
            ConvertToProjectAction::make(),
            DuplicateQuoteAction::make(),
            SendQuoteAction::make(),
            ResendQuoteAction::make(),

            // Moneybird dropdown
            ActionGroup::make([
                SendToMoneybirdAction::make(),
                UpdateMoneybirdAction::make(),
                SyncFromMoneybirdAction::make(),
            ])
                ->label('Moneybird')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('gray')
                ->button(),

            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
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

        // Refresh the form so the calculated totals are visible immediately
        $this->refreshFormData(['subtotal', 'vat_amount', 'total']);
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
