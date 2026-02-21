<?php

namespace App\Filament\Resources\Companies\Actions;

use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ImportAllFromMoneybirdAction extends Action
{
    public static function make(?string $name = 'importAllFromMoneybird'): static
    {
        return parent::make($name)
            ->label('Importeer alle contacten')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Alle contacten importeren uit Moneybird')
            ->modalDescription('Weet je zeker dat je alle contacten uit Moneybird wilt importeren? Dit kan even duren bij veel contacten.')
            ->modalSubmitActionLabel('Ja, alles importeren')
            ->action(function (MoneybirdService $moneybirdService) {
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

                    // Import all contacts from Moneybird
                    $stats = $moneybirdService->importAllContacts();

                    // Show success notification with statistics
                    $message = sprintf(
                        'Nieuw: %d | Bijgewerkt: %d | Overgeslagen: %d',
                        $stats['imported'],
                        $stats['updated'],
                        $stats['skipped']
                    );

                    Notification::make()
                        ->success()
                        ->title('Contacten geïmporteerd')
                        ->body($message)
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Import mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
