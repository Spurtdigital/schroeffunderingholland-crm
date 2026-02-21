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
                                if (! $moneybirdService->isConfigured()) {
                                    return [];
                                }

                                try {
                                    $moneybird = $moneybirdService->getMoneybirdInstance();
                                    if (! $moneybird) {
                                        return [];
                                    }

                                    $contacts = $moneybird->contact()->get();
                                    $options = [];

                                    foreach ($contacts as $contact) {
                                        $label = $contact->company_name ?: ($contact->firstname.' '.$contact->lastname);
                                        $options[$contact->id] = $label.' ('.($contact->email ?: 'Geen email').')';
                                    }

                                    return $options;
                                } catch (\Exception $e) {
                                    return [];
                                }
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

                                    // Fill form with Moneybird data
                                    $set('name', $contact->company_name ?: 'Onbekend');
                                    $set('email', $contact->email);
                                    $set('phone', $contact->phone);
                                    $set('street', $contact->address1);
                                    $set('house_number', $contact->address2 ?: '');
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
                            ->helperText('Selecteer een contact om de gegevens automatisch in te vullen')
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
