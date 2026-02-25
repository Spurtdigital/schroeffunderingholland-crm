<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Supplier;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Algemeen')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('sku')
                            ->label('Artikelnummer')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->label('Actief')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('short_description')
                            ->label('Korte omschrijving')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Wordt gebruikt op offertes en facturen'),
                        Textarea::make('description')
                            ->label('Interne omschrijving')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Alleen intern zichtbaar'),
                    ]),

                Section::make('Leverancier')
                    ->columns(2)
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Leverancier')
                            ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Selecteer leverancier...')
                            ->columnSpanFull(),

                        TextInput::make('supplier_article_number')
                            ->label('Artikelnummer leverancier')
                            ->maxLength(100)
                            ->placeholder('bijv. SFH-001')
                            ->helperText('Artikelnummer zoals de leverancier het hanteert'),

                        TextInput::make('weight')
                            ->label('Gewicht (kg)')
                            ->numeric()
                            ->suffix('kg')
                            ->step('0.001')
                            ->minValue(0)
                            ->placeholder('bijv. 1.250'),
                    ]),

                Section::make('Prijzen & Voorraad')
                    ->columns(2)
                    ->schema([
                        TextInput::make('purchase_price')
                            ->label('Inkoopprijs')
                            ->numeric()
                            ->prefix('€')
                            ->step('0.01')
                            ->required(),
                        TextInput::make('sales_price')
                            ->label('Verkoopprijs')
                            ->numeric()
                            ->prefix('€')
                            ->step('0.01')
                            ->required(),
                        TextInput::make('min_stock_level')
                            ->label('Minimum voorraad')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Wordt gebruikt voor waarschuwingen bij lage voorraad'),
                        Placeholder::make('stock_quantity')
                            ->label('Huidige voorraad')
                            ->content(fn ($record) => $record ? $record->stock_quantity.' stuks' : '—')
                            ->helperText('Pas voorraad aan via de "Voorraad toevoegen" actie'),
                    ]),
            ]);
    }
}
