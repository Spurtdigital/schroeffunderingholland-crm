<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\QuoteItemType;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Project items';

    protected static ?string $modelLabel = 'item';

    protected static ?string $pluralModelLabel = 'items';

    // Default form for editing existing items
    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options(QuoteItemType::class)
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(3),

                Forms\Components\TextInput::make('description')
                    ->label('Omschrijving')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(3),

                Forms\Components\TextInput::make('quantity')
                    ->label('Aantal')
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                    ->columnSpan(1),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Prijs per stuk (excl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('quantity') ?? 1), 2)))
                    ->columnSpan(1),

                Forms\Components\TextInput::make('total')
                    ->label('Totaal (excl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Omschrijving')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Aantal')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Prijs')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Totaal')
                    ->money('EUR'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(QuoteItemType::class),
            ])
            ->headerActions([
                // Add Product
                Action::make('addProduct')
                    ->label('Product toevoegen')
                    ->icon('heroicon-o-cube')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('itemable_id')
                            ->label('Selecteer product')
                            ->placeholder('Kies een product...')
                            ->options(Product::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('description', $product->name);
                                        $set('unit_price', $product->sales_price);
                                    }
                                }
                            })
                            ->helperText('Product wordt automatisch ingevuld')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('description')
                            ->label('Omschrijving')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Aantal')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Prijs per stuk (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('quantity') ?? 1), 2)))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total')
                            ->label('Totaal (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $data['type'] = QuoteItemType::Product->value;
                        $data['itemable_type'] = Product::class;
                        $data['total'] = round(($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0), 2);

                        $project = $livewire->getOwnerRecord();
                        $project->items()->create($data);

                        Notification::make()
                            ->success()
                            ->title('Product toegevoegd')
                            ->send();
                    }),

                // Add Free Line
                Action::make('addFreeLine')
                    ->label('Vrije regel toevoegen')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->form([
                        Forms\Components\TextInput::make('description')
                            ->label('Omschrijving')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Aantal')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Prijs per stuk (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('quantity') ?? 1), 2)))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total')
                            ->label('Totaal (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $data['type'] = QuoteItemType::FreeLine->value;
                        $data['itemable_type'] = null;
                        $data['itemable_id'] = null;
                        $data['total'] = round(($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0), 2);

                        $project = $livewire->getOwnerRecord();
                        $project->items()->create($data);

                        Notification::make()
                            ->success()
                            ->title('Vrije regel toegevoegd')
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Bewerken'),
                DeleteAction::make()
                    ->label('Verwijderen'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Verwijderen'),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
