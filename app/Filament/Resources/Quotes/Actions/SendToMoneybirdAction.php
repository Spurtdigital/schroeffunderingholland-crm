<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\MoneybirdService;
use App\Services\QuoteCalculationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SendToMoneybirdAction extends Action
{
    public static function make(?string $name = 'sendToMoneybird'): static
    {
        return parent::make($name)
            ->label('Verstuur naar Moneybird')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Versturen naar Moneybird')
            ->modalDescription('Deze offerte wordt als concept naar Moneybird verstuurd. Je kunt hem daar afronden en versturen naar de klant.')
            ->modalSubmitActionLabel('Ja, versturen')
            ->visible(fn (Quote $record) => ! $record->moneybird_quote_id)
            ->action(function (Quote $record, MoneybirdService $moneybirdService, QuoteCalculationService $calculationService) {
                try {
                    // Check if Moneybird is configured
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()
                            ->danger()
                            ->title('Moneybird niet geconfigureerd')
                            ->body('Configureer eerst de Moneybird API instellingen.')
                            ->send();

                        return;
                    }

                    // Check if quote has items
                    if ($record->items()->count() === 0) {
                        Notification::make()
                            ->warning()
                            ->title('Geen regels')
                            ->body('Voeg eerst regels toe aan de offerte voordat je deze verstuurt naar Moneybird.')
                            ->send();

                        return;
                    }

                    // Ensure totals are up to date before sending
                    $calculationService->calculateTotals($record);
                    $record->refresh();

                    // Send quote to Moneybird
                    $moneybirdService->sendQuote($record);

                    // Update quote status
                    $record->update([
                        'status' => QuoteStatus::MoneybirdPrepared,
                    ]);

                    // Show success notification
                    Notification::make()
                        ->success()
                        ->title('Offerte verstuurd')
                        ->body("Offerte {$record->quote_number} is succesvol verstuurd naar Moneybird.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Verzenden mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
