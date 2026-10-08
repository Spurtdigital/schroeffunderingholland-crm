<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Services\PostcodeService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Klantnaam')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('contact_person')
                    ->label('Voornaam contactpersoon')
                    ->maxLength(255)
                    ->helperText('Wordt gebruikt als aanhef in de offerte: "Beste [voornaam],"'),
                TextInput::make('email')
                    ->label('E-mailadres')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Telefoonnummer')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('postal_code')
                    ->label('Postcode')
                    ->maxLength(10)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, ?string $old, $set, callable $get) {
                        $houseNumber = $get('house_number');

                        if (! $state || ! $houseNumber || $state === $old) {
                            return;
                        }

                        $postcodeService = app(PostcodeService::class);
                        $result = $postcodeService->lookup($state, $houseNumber);

                        if ($result) {
                            $set('street', $result['street']);
                            $set('city', $result['city']);

                            Notification::make()
                                ->success()
                                ->title('Adres gevonden')
                                ->body('Straat en plaats automatisch ingevuld')
                                ->send();
                        }
                    }),
                TextInput::make('house_number')
                    ->label('Huisnummer')
                    ->maxLength(20)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, ?string $old, $set, callable $get) {
                        $postalCode = $get('postal_code');

                        if (! $state || ! $postalCode || $state === $old) {
                            return;
                        }

                        $postcodeService = app(PostcodeService::class);
                        $result = $postcodeService->lookup($postalCode, $state);

                        if ($result) {
                            $set('street', $result['street']);
                            $set('city', $result['city']);

                            Notification::make()
                                ->success()
                                ->title('Adres gevonden')
                                ->body('Straat en plaats automatisch ingevuld')
                                ->send();
                        }
                    }),
                TextInput::make('street')
                    ->label('Straat')
                    ->maxLength(255),
                TextInput::make('city')
                    ->label('Plaats')
                    ->maxLength(255),
                TextInput::make('moneybird_contact_id')
                    ->label('Moneybird ID')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Wordt automatisch gevuld bij sync met Moneybird')
                    ->columnSpanFull(),
            ]);
    }
}
