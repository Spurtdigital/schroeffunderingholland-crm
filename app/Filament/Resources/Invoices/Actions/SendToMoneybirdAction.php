<?php

namespace App\Filament\Resources\Invoices\Actions;

use App\Models\Invoice;
use App\Services\MoneybirdService;
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
            ->modalDescription('Weet je zeker dat je deze factuur wilt versturen naar Moneybird?')
            ->modalSubmitActionLabel('Ja, versturen')
            ->visible(fn (Invoice $record) => ! $record->moneybird_invoice_id)
            ->action(function (Invoice $record, MoneybirdService $moneybirdService) {
                try {
                    // Check if Moneybird is configured
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()
                            ->danger()
                            ->title('Moneybird niet geconfigureerd')
                            ->body('Configureer eerst de Moneybird API instellingen in de instellingen pagina.')
                            ->send();

                        return;
                    }

                    // Send invoice to Moneybird
                    $moneybirdService->sendInvoice($record);

                    // Show success notification
                    Notification::make()
                        ->success()
                        ->title('Factuur verstuurd')
                        ->body("Factuur {$record->invoice_number} is succesvol verstuurd naar Moneybird.")
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
