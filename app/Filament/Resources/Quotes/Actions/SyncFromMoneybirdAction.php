<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SyncFromMoneybirdAction extends Action
{
    public static function make(?string $name = 'syncFromMoneybird'): static
    {
        return parent::make($name)
            ->label('Status ophalen uit Moneybird')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->visible(fn (Quote $record) => (bool) $record->moneybird_quote_id)
            ->action(function (Quote $record, MoneybirdService $moneybirdService) {
                try {
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()
                            ->danger()
                            ->title('Moneybird niet geconfigureerd')
                            ->body('Configureer eerst de Moneybird API instellingen.')
                            ->send();

                        return;
                    }

                    $moneybird = $moneybirdService->getMoneybirdInstance();
                    $estimate = $moneybird->estimate()->find($record->moneybird_quote_id);

                    // Map Moneybird state to CRM status
                    $newStatus = self::mapMoneybirdStateToCrmStatus($estimate);

                    // Also check if quote_number needs updating (CONCEPT → Moneybird estimate number)
                    $quoteNumberNeedsUpdate = $estimate->estimate_id
                        && str_starts_with($record->quote_number ?? '', 'CONCEPT-');

                    if (! $newStatus && ! $quoteNumberNeedsUpdate) {
                        Notification::make()
                            ->info()
                            ->title('Geen wijziging')
                            ->body("Status in Moneybird is '{$estimate->state}'. Geen update nodig.")
                            ->send();

                        return;
                    }

                    if ($record->status === $newStatus && ! $quoteNumberNeedsUpdate) {
                        Notification::make()
                            ->info()
                            ->title('Status al actueel')
                            ->body("Status is al '{$newStatus->getLabel()}' in beide systemen.")
                            ->send();

                        return;
                    }

                    // Prepare update data
                    $updateData = [];

                    if ($newStatus) {
                        $updateData['status'] = $newStatus;
                    }

                    // Set timestamp fields based on status
                    if ($newStatus === QuoteStatus::Sent && ! $record->sent_at && $estimate->sent_at) {
                        $updateData['sent_at'] = \Carbon\Carbon::parse($estimate->sent_at);
                    }

                    if ($newStatus === QuoteStatus::Approved && ! $record->approved_at && $estimate->accepted_at) {
                        $updateData['approved_at'] = \Carbon\Carbon::parse($estimate->accepted_at);
                    }

                    // Replace CONCEPT-number with Moneybird estimate number if still a placeholder
                    if ($estimate->estimate_id && str_starts_with($record->quote_number ?? '', 'CONCEPT-')) {
                        $updateData['quote_number'] = $estimate->estimate_id;
                    }

                    // Update quote
                    $oldStatus = $record->status;
                    $record->update($updateData);

                    $statusChanged = $newStatus && $newStatus !== $oldStatus;
                    $numberChanged = isset($updateData['quote_number']);

                    $bodyParts = [];
                    if ($statusChanged) {
                        $bodyParts[] = "Status: '{$oldStatus->getLabel()}' → '{$newStatus->getLabel()}'";
                    }
                    if ($numberChanged) {
                        $bodyParts[] = "Offertenummer bijgewerkt naar {$updateData['quote_number']}";
                    }

                    Notification::make()
                        ->success()
                        ->title('Gesynchroniseerd vanuit Moneybird')
                        ->body(implode("\n", $bodyParts))
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Synchronisatie mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    protected static function mapMoneybirdStateToCrmStatus($estimate): ?QuoteStatus
    {
        // Priority: rejected > accepted > sent > draft

        if ($estimate->rejected_at) {
            return QuoteStatus::Rejected;
        }

        if ($estimate->accepted_at) {
            return QuoteStatus::Approved;
        }

        if ($estimate->sent_at) {
            return QuoteStatus::Sent;
        }

        // Keep current status if still draft
        return null;
    }
}
