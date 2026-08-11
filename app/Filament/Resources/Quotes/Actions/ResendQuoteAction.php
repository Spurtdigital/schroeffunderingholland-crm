<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Notifications\CustomerQuoteNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ResendQuoteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resendQuote';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Nogmaals versturen');

        $this->icon('heroicon-o-arrow-path');

        $this->color('gray');

        $this->modalHeading('Offerte nogmaals versturen');

        $this->modalDescription('Verstuur de offerte opnieuw per e-mail met de PDF als bijlage. De status en voorraadreserveringen blijven ongewijzigd.');

        $this->modalSubmitActionLabel('Opnieuw versturen');

        $this->form(fn (Model $record) => [
            TextInput::make('contact_person')
                ->label('T.a.v. (contactpersoon)')
                ->default($record->company?->contact_person)
                ->maxLength(255),

            TextInput::make('email')
                ->label('E-mailadres klant')
                ->email()
                ->default($record->company?->email)
                ->required()
                ->maxLength(255),

            Textarea::make('custom_message')
                ->label('Eigen mailtekst (optioneel)')
                ->helperText('Laat leeg om de standaard e-mailtekst te gebruiken. Vul hier een eigen tekst in om de standaard body te vervangen.')
                ->rows(4)
                ->maxLength(5000),
        ]);

        $this->visible(fn (Model $record): bool => in_array($record->status, [
            QuoteStatus::Sent,
            QuoteStatus::Approved,
            QuoteStatus::Converted,
        ], true));

        $this->action(function (Model $record, array $data) {
            NotificationFacade::route('mail', $data['email'])->notify(
                new CustomerQuoteNotification($record->fresh(), $data['contact_person'] ?? null, $data['custom_message'] ?? null)
            );

            Notification::make()
                ->success()
                ->title('Offerte opnieuw verstuurd')
                ->body("Offerte ".($record->displayNumber() ?? 'Concept')." is opnieuw per e-mail met PDF-bijlage verstuurd.")
                ->send();
        });
    }
}
