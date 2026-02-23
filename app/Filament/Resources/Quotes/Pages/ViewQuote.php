<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Actions\ApproveQuoteAction;
use App\Filament\Resources\Quotes\Actions\ConvertToProjectAction;
use App\Filament\Resources\Quotes\Actions\GeneratePdfAction;
use App\Filament\Resources\Quotes\Actions\SendQuoteAction;
use App\Filament\Resources\Quotes\Actions\SendToMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\SyncFromMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\UpdateMoneybirdAction;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\Quotes\Schemas\QuoteForm;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Services\QuoteCalculationService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->color('primary')
                ->modalHeading('Offerte bewerken')
                ->modalWidth('5xl')
                ->form(fn ($form) => QuoteForm::configure($form))
                ->after(function (Quote $record, array $data) {
                    // Herbereken totalen
                    app(QuoteCalculationService::class)->calculateTotals($record);

                    // Sync handmatig geselecteerde aannames
                    $assumptionIds = $data['assumption_ids'] ?? [];
                    $record->assumptions()->sync($assumptionIds);

                    // Auto-sync SKU-gekoppelde aannames
                    $this->autoSyncSkuAssumptions($record);

                    // Refresh infolist
                    $this->refreshFormData([
                        'status', 'subtotal', 'vat_amount', 'total',
                        'pre_text', 'post_text', 'payment_term_id',
                        'valid_until', 'notes', 'sent_at',
                    ]);
                }),

            GeneratePdfAction::make(),
            SendQuoteAction::make(),
            ApproveQuoteAction::make(),
            ConvertToProjectAction::make(),
            SendToMoneybirdAction::make(),
            UpdateMoneybirdAction::make(),
            SyncFromMoneybirdAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    /**
     * Check quote items for SKU matches and auto-add triggered assumptions.
     */
    protected function autoSyncSkuAssumptions(Quote $record): void
    {
        $skus = $record->items()
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
            $record->assumptions()->syncWithoutDetaching($triggered->toArray());
        }
    }
}
