<?php

namespace App\Filament\Resources\Companies\Actions;

use App\Models\Company;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SyncToMoneybirdAction extends Action
{
    public static function make(?string $name = 'syncToMoneybird'): static
    {
        return parent::make($name)
            ->label('Sync naar Moneybird')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Synchroniseren naar Moneybird')
            ->modalDescription('Weet je zeker dat je deze klant wilt synchroniseren naar Moneybird?')
            ->modalSubmitActionLabel('Ja, synchroniseren')
            ->action(function (Company $record, MoneybirdService $moneybirdService) {
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

                    // Sync company to Moneybird
                    $moneybirdService->syncCompany($record);

                    // Show success notification
                    Notification::make()
                        ->success()
                        ->title('Klant gesynchroniseerd')
                        ->body("Klant {$record->name} is succesvol gesynchroniseerd naar Moneybird.")
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
}
