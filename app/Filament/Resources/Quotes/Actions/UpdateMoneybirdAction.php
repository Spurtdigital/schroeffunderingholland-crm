<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Models\Quote;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class UpdateMoneybirdAction extends Action
{
    public static function make(?string $name = 'updateMoneybird'): static
    {
        return parent::make($name)
            ->label('Bijwerken in Moneybird')
            ->icon('heroicon-o-arrow-up-circle')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Offerte bijwerken in Moneybird')
            ->modalDescription('De regels van deze offerte worden overschreven in Moneybird met de huidige gegevens. Dit kan niet ongedaan worden gemaakt.')
            ->modalSubmitActionLabel('Ja, bijwerken')
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

                    $moneybirdService->updateQuote($record);

                    Notification::make()
                        ->success()
                        ->title('Offerte bijgewerkt')
                        ->body("Offerte {$record->quote_number} is bijgewerkt in Moneybird.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Bijwerken mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
