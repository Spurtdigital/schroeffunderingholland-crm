<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Klantgegevens')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Klantnaam')
                            ->weight('bold'),
                        TextEntry::make('contact_person')
                            ->label('Contactpersoon')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('E-mailadres')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label('Telefoonnummer')
                            ->placeholder('—'),
                        TextEntry::make('street')
                            ->label('Straat')
                            ->placeholder('—'),
                        TextEntry::make('house_number')
                            ->label('Huisnummer')
                            ->placeholder('—'),
                        TextEntry::make('postal_code')
                            ->label('Postcode')
                            ->placeholder('—'),
                        TextEntry::make('city')
                            ->label('Plaats')
                            ->placeholder('—'),
                        TextEntry::make('moneybird_contact_id')
                            ->label('Moneybird ID')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Aangemaakt op')
                            ->dateTime('d-m-Y H:i'),
                    ]),
            ]);
    }
}
