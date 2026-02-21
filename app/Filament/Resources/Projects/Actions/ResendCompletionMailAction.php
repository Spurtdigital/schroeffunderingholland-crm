<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Project;
use App\Notifications\ProjectCompletedNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ResendCompletionMailAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resendCompletionMail';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Mail opnieuw versturen');
        $this->icon('heroicon-o-envelope');
        $this->color('info');

        $this->visible(fn (Project $record) => $record->isCompleted());

        $this->requiresConfirmation();
        $this->modalHeading('Oplevermail opnieuw versturen');
        $this->modalDescription(fn (Project $record) => "Weet je zeker dat je de oplevermail voor project {$record->project_number} opnieuw wilt versturen naar {$record->company?->email}?");
        $this->modalSubmitActionLabel('Versturen');
        $this->modalIcon('heroicon-o-envelope');

        $this->action(function (Project $record) {
            $company = $record->company;

            if (! $company || ! $company->email) {
                Notification::make()
                    ->warning()
                    ->title('Geen e-mailadres')
                    ->body('Dit bedrijf heeft geen e-mailadres ingesteld.')
                    ->send();

                return;
            }

            try {
                $company->notify(new ProjectCompletedNotification($record));

                Notification::make()
                    ->success()
                    ->title('Mail verstuurd')
                    ->body("De oplevermail voor project {$record->project_number} is opnieuw verstuurd naar {$company->email}.")
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->danger()
                    ->title('Mail versturen mislukt')
                    ->body($e->getMessage())
                    ->send();
            }
        });
    }
}
