<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Regels';

    protected static ?string $modelLabel = 'regel';

    protected static ?string $pluralModelLabel = 'regels';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Forms\Components\Select::make('product_id')
                    ->label('Product (optioneel)')
                    ->options(fn () => Product::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('description', $product->name);
                                $set('supplier_article_number', $product->supplier_article_number ?? '');
                                $set('unit_price', (float) $product->purchase_price);
                            }
                        }
                    })
                    ->columnSpan(3),

                Forms\Components\TextInput::make('description')
                    ->label('Omschrijving')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(3),

                Forms\Components\TextInput::make('supplier_article_number')
                    ->label('Leverancier artikelnummer')
                    ->maxLength(100)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('quantity')
                    ->label('Aantal')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(0.01)
                    ->step(0.01)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $set('total', round((float) $state * (float) $get('unit_price'), 2));
                    })
                    ->columnSpan(1),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Eenheidsprijs')
                    ->numeric()
                    ->required()
                    ->prefix('€')
                    ->minValue(0)
                    ->step(0.01)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $set('total', round((float) $get('quantity') * (float) $state, 2));
                    })
                    ->columnSpan(1),

                Forms\Components\TextInput::make('total')
                    ->label('Totaal')
                    ->numeric()
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Omschrijving')
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplier_article_number')
                    ->label('Leverancier art.nr.')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Aantal')
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Eenheidsprijs')
                    ->money('EUR'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Totaal')
                    ->money('EUR'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nieuwe regel')
                    ->after(function () {
                        $this->getOwnerRecord()->refresh()->recalculateTotals();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Bewerken')
                    ->after(function () {
                        $this->getOwnerRecord()->refresh()->recalculateTotals();
                    }),
                DeleteAction::make()
                    ->label('Verwijderen')
                    ->after(function () {
                        $this->getOwnerRecord()->refresh()->recalculateTotals();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Verwijderen')
                        ->after(function () {
                            $this->getOwnerRecord()->refresh()->recalculateTotals();
                        }),
                ]),
            ]);
    }
}
