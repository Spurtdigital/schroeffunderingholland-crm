<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Supplier;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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

                Section::make('Leveranciers')
                    ->description('Voeg één of meerdere leveranciers toe met hun artikelnummer en inkoopprijs.')
                    ->schema([
                        Repeater::make('product_suppliers')
                            ->label('')
                            ->schema([
                                Select::make('supplier_id')
                                    ->label('Leverancier')
                                    ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Selecteer leverancier...')
                                    ->columnSpan(2),

                                TextInput::make('supplier_sku')
                                    ->label('Art.nr. leverancier')
                                    ->maxLength(100)
                                    ->placeholder('bijv. SFH-001')
                                    ->columnSpan(1),

                                TextInput::make('purchase_price')
                                    ->label('Inkoopprijs')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step('0.01')
                                    ->required()
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Toggle::make('is_preferred')
                                    ->label('Voorkeur')
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->addActionLabel('Leverancier toevoegen')
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['supplier_id']
                                ? Supplier::find($state['supplier_id'])?->name
                                : 'Nieuwe leverancier'
                            ),
                    ]),

                Section::make('Prijzen & Voorraad')
                    ->columns(2)
                    ->schema([
                        TextInput::make('purchase_price')
                            ->label('Standaard inkoopprijs')
                            ->numeric()
                            ->prefix('€')
                            ->step('0.01')
                            ->required()
                            ->helperText('Fallback als er geen leverancier-specifieke prijs is'),
                        TextInput::make('sales_price')
                            ->label('Verkoopprijs')
                            ->numeric()
                            ->prefix('€')
                            ->step('0.01')
                            ->required(),
                        TextInput::make('weight')
                            ->label('Gewicht (kg)')
                            ->numeric()
                            ->suffix('kg')
                            ->step('0.001')
                            ->minValue(0)
                            ->placeholder('bijv. 1.250'),
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
