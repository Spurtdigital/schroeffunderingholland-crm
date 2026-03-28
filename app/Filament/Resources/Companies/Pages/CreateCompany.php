<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\MoneybirdService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Importeer van Moneybird')
                    ->description('Je kunt optioneel een bestaande klant uit Moneybird importeren.')
                    ->schema([
                        Select::make('moneybird_contact_selector')
                            ->label('Selecteer Moneybird contact')
                            ->searchable()
                            ->options(function (MoneybirdService $moneybirdService) {
                                $contacts = MoneybirdService::getCachedContacts();

                                if (empty($contacts)) {
                                    return [];
                                }

                                $options = [];
                                foreach ($contacts as $contact) {
                                    $label = $contact['name'];
                                    if (! empty($contact['email'])) {
                                        $label .= ' ('.$contact['email'].')';
                                    }
                                    $options[$contact['id']] = $label;
                                }

                                return $options;
                            })
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, MoneybirdService $moneybirdService) {
                                if (! $state) {
                                    return;
                                }

                                try {
                                    $moneybird = $moneybirdService->getMoneybirdInstance();
                                    if (! $moneybird) {
                                        return;
                                    }

                                    $contact = $moneybird->contact()->find($state);

                                    // Parse street and house number from Moneybird address1
                                    [$street, $houseNumber] = \App\Services\MoneybirdService::parseAddress($contact->address1);

                                    // Fill form with Moneybird data
                                    $set('name', $contact->company_name ?: 'Onbekend');
                                    $set('email', $contact->email);
                                    $set('phone', $contact->phone);
                                    $set('street', $street);
                                    $set('house_number', $houseNumber ?: '');
                                    $set('postal_code', $contact->zipcode);
                                    $set('city', $contact->city);
                                    $set('moneybird_contact_id', $contact->id);

                                    Notification::make()
                                        ->success()
                                        ->title('Gegevens geladen van Moneybird')
                                        ->body('De klantgegevens zijn automatisch ingevuld.')
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Fout bij ophalen gegevens')
                                        ->body($e->getMessage())
                                        ->send();
                                }
                            })
                            ->helperText('Selecteer een contact om de gegevens automatisch in te vullen. Gebruik "Klanten synchroniseren" in de instellingen om de lijst bij te werken.')
                            ->placeholder('Kies een Moneybird contact (optioneel)')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),

                // Include the default company form fields
                ...CompanyResource::form($schema)->getComponents(),
            ]);
    }
}
