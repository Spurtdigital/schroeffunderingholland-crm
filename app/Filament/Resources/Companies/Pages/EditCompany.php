<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /**
     * Automatically sync to Moneybird after saving
     */
    protected function afterSave(): void
    {
        // Only sync if company already exists in Moneybird
        if ($this->record->moneybird_contact_id) {
            try {
                $moneybirdService = app(MoneybirdService::class);

                if ($moneybirdService->isConfigured()) {
                    $moneybirdService->syncCompany($this->record);

                    Notification::make()
                        ->success()
                        ->title('Gegevens bijgewerkt in Moneybird')
                        ->body('De klantgegevens zijn automatisch gesynchroniseerd met Moneybird.')
                        ->send();
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->warning()
                    ->title('Moneybird sync mislukt')
                    ->body('De gegevens zijn lokaal opgeslagen, maar konden niet worden gesynchroniseerd met Moneybird: '.$e->getMessage())
                    ->send();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendToMoneybird')
                ->label('Verstuur naar Moneybird')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('info')
                ->visible(fn () => ! $this->record->moneybird_contact_id)
                ->requiresConfirmation()
                ->modalHeading('Klant naar Moneybird versturen')
                ->modalDescription('Weet je zeker dat je deze klant naar Moneybird wilt versturen? Er wordt een nieuw contact aangemaakt in Moneybird.')
                ->action(function (MoneybirdService $moneybirdService) {
                    try {
                        if (! $moneybirdService->isConfigured()) {
                            Notification::make()
                                ->warning()
                                ->title('Moneybird niet geconfigureerd')
                                ->body('Configureer eerst de Moneybird API instellingen.')
                                ->send();

                            return;
                        }

                        $moneybirdService->syncCompany($this->record);

                        Notification::make()
                            ->success()
                            ->title('Klant verstuurd naar Moneybird')
                            ->body('De klant is succesvol verstuurd naar Moneybird.')
                            ->send();

                        // Refresh the page to show updated data
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Fout bij versturen naar Moneybird')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }
}
