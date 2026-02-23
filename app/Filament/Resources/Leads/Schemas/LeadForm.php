<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use App\Services\PostcodeService;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('company_id')
                    ->label('Bedrijf')
                    ->relationship('company', 'name', fn ($query) => $query->orderBy('name'))
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Bedrijfsnaam')
                            ->required(),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email(),
                        TextInput::make('phone')
                            ->label('Telefoon'),
                        TextInput::make('street')
                            ->label('Straat'),
                        TextInput::make('house_number')
                            ->label('Huisnummer'),
                        TextInput::make('postal_code')
                            ->label('Postcode'),
                        TextInput::make('city')
                            ->label('Plaats'),
                    ])
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Status')
                    ->options(LeadStatus::class)
                    ->default(LeadStatus::New)
                    ->required()
                    ->columnSpan(1),

                Select::make('converted_to_quote_id')
                    ->label('Geconverteerd naar offerte')
                    ->relationship('convertedToQuote', 'quote_number')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->columnSpan(1),

                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('phone')
                    ->label('Telefoon')
                    ->tel()
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('postal_code')
                    ->label('Postcode')
                    ->maxLength(10)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, ?string $old, $set, callable $get) {
                        Log::info('Postal code updated', [
                            'state' => $state,
                            'old' => $old,
                            'house_number' => $get('house_number'),
                        ]);

                        // Only lookup if postal code and house number are filled
                        $houseNumber = $get('house_number');

                        if (! $state || ! $houseNumber) {
                            Log::info('Skipping lookup - missing postal code or house number');

                            return;
                        }

                        // Only lookup if postal code actually changed
                        if ($state === $old) {
                            Log::info('Skipping lookup - postal code not changed');

                            return;
                        }

                        Log::info('Performing postcode lookup', ['postal_code' => $state, 'house_number' => $houseNumber]);

                        $postcodeService = app(PostcodeService::class);
                        $result = $postcodeService->lookup($state, $houseNumber);

                        Log::info('Lookup result', ['result' => $result]);

                        if ($result) {
                            $set('street', $result['street']);
                            $set('city', $result['city']);

                            Log::info('Set street and city', ['street' => $result['street'], 'city' => $result['city']]);

                            Notification::make()
                                ->success()
                                ->title('Adres gevonden')
                                ->body("Straat en plaats automatisch ingevuld voor {$state}")
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Adres niet gevonden')
                                ->body('Vul straat en plaats handmatig in')
                                ->send();
                        }
                    })
                    ->columnSpan(1),

                TextInput::make('house_number')
                    ->label('Huisnummer')
                    ->maxLength(20)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, ?string $old, $set, callable $get) {
                        // Only lookup if postal code and house number are filled
                        $postalCode = $get('postal_code');

                        if (! $state || ! $postalCode) {
                            return;
                        }

                        // Only lookup if house number actually changed
                        if ($state === $old) {
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
                                ->body("Straat en plaats automatisch ingevuld voor {$postalCode} {$state}")
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Adres niet gevonden')
                                ->body('Vul straat en plaats handmatig in')
                                ->send();
                        }
                    })
                    ->columnSpan(1),

                TextInput::make('street')
                    ->label('Straat')
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('city')
                    ->label('Plaats')
                    ->maxLength(255)
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->label('Opmerkingen / Projectomschrijving')
                    ->rows(4)
                    ->columnSpanFull(),

                Section::make('Interne notities')
                    ->description('Vastgelegd door het systeem, bijv. via "Meer info nodig" actie.')
                    ->schema([
                        Textarea::make('internal_notes')
                            ->label('Interne notities')
                            ->rows(5)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Nog geen interne notities.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => blank($record?->internal_notes))
                    ->columnSpanFull(),

                KeyValue::make('request_data')
                    ->label('Formulier gegevens (CF7)')
                    ->keyLabel('Veld')
                    ->valueLabel('Waarde')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
