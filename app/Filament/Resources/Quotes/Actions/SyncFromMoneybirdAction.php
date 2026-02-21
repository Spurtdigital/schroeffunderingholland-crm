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

                    if (! $newStatus) {
                        Notification::make()
                            ->info()
                            ->title('Geen wijziging')
                            ->body("Status in Moneybird is nog '{$estimate->state}' (concept). Geen update nodig.")
                            ->send();

                        return;
                    }

                    if ($record->status === $newStatus) {
                        Notification::make()
                            ->info()
                            ->title('Status al actueel')
                            ->body("Status is al '{$newStatus->getLabel()}' in beide systemen.")
                            ->send();

                        return;
                    }

                    // Prepare update data
                    $updateData = ['status' => $newStatus];

                    // Set timestamp fields based on status
                    if ($newStatus === QuoteStatus::Approved && ! $record->approved_at && $estimate->accepted_at) {
                        $updateData['approved_at'] = \Carbon\Carbon::parse($estimate->accepted_at);
                    }

                    // Update status
                    $oldStatus = $record->status;
                    $record->update($updateData);

                    Notification::make()
                        ->success()
                        ->title('Status gesynchroniseerd')
                        ->body("Status gewijzigd van '{$oldStatus->getLabel()}' naar '{$newStatus->getLabel()}'")
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
