<?php

namespace App\Filament\Resources\Quotes\RelationManagers;

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Services\DistanceCalculatorService;
use App\Services\QuoteCalculationService;
use App\Settings\GeneralSettings;
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

    protected static ?string $title = 'Regels';

    protected static ?string $modelLabel = 'regel';

    protected static ?string $pluralModelLabel = 'regels';

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
                    ->minValue(0.01)
                    ->step(0.01)
                    ->live()
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                    ->columnSpan(1),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Prijs per stuk (excl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->live()
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
                            ->minValue(0.01)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Prijs per stuk (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->live()
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

                        $quote = $livewire->getOwnerRecord();
                        $quote->items()->create($data);

                        // Force recalculation of quote totals
                        app(QuoteCalculationService::class)->calculateTotals($quote);

                        Notification::make()
                            ->success()
                            ->title('Product toegevoegd')
                            ->send();
                    }),

                // Add Mileage (automatically calculated)
                Action::make('addMileage')
                    ->label('Reiskosten toevoegen')
                    ->icon('heroicon-o-map-pin')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Reiskosten automatisch berekenen')
                    ->modalDescription('De afstand wordt automatisch berekend via Google Maps naar het adres van het bedrijf.')
                    ->modalSubmitActionLabel('Bereken en toevoegen')
                    ->action(function (RelationManager $livewire): void {
                        $distanceService = app(DistanceCalculatorService::class);
                        $settings = app(GeneralSettings::class);
                        $quote = $livewire->getOwnerRecord();

                        // Validation checks
                        if (! $distanceService->isAvailable()) {
                            Notification::make()
                                ->warning()
                                ->title('Google Maps niet geconfigureerd')
                                ->body('Vul eerst de Google Maps API key en bedrijfsadres in bij Instellingen.')
                                ->send();

                            return;
                        }

                        $company = $quote->company;
                        if (! $company) {
                            Notification::make()
                                ->warning()
                                ->title('Geen bedrijf gevonden')
                                ->body('Deze offerte heeft geen bedrijf gekoppeld.')
                                ->send();

                            return;
                        }

                        $address = $distanceService->buildAddressFromCompany($company);
                        if (! $address) {
                            Notification::make()
                                ->warning()
                                ->title('Geen adres beschikbaar')
                                ->body('Het bedrijf heeft geen volledig adres.')
                                ->send();

                            return;
                        }

                        // Calculate distance
                        $result = $distanceService->calculateDistance($address);

                        if ($result['success']) {
                            $pricePerKm = $settings->default_travel_cost_per_km ?? 0.23;
                            $total = round($result['distance_km'] * $pricePerKm, 2);

                            // Create the quote item
                            $quote->items()->create([
                                'type' => QuoteItemType::Mileage->value,
                                'description' => "Reiskosten ({$result['distance_text']})",
                                'quantity' => $result['distance_km'],
                                'unit_price' => $pricePerKm,
                                'total' => $total,
                                'itemable_type' => null,
                                'itemable_id' => null,
                            ]);

                            // Force recalculation of quote totals
                            app(QuoteCalculationService::class)->calculateTotals($quote);

                            Notification::make()
                                ->success()
                                ->title('Reiskosten toegevoegd')
                                ->body("{$result['distance_text']} × €{$pricePerKm} = €{$total} (ca. {$result['duration_text']})")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Afstand berekenen mislukt')
                                ->body($result['error'] ?? 'Kon de afstand niet berekenen.')
                                ->send();
                        }
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
                            ->live()
                            ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Prijs per stuk (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->live()
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

                        $quote = $livewire->getOwnerRecord();
                        $quote->items()->create($data);

                        // Force recalculation of quote totals
                        app(QuoteCalculationService::class)->calculateTotals($quote);

                        Notification::make()
                            ->success()
                            ->title('Vrije regel toegevoegd')
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Bewerken')
                    ->after(function ($record, RelationManager $livewire) {
                        // Recalculate totals after editing
                        $quote = $livewire->getOwnerRecord();
                        app(QuoteCalculationService::class)->calculateTotals($quote);
                    }),
                DeleteAction::make()
                    ->label('Verwijderen')
                    ->after(function ($record, RelationManager $livewire) {
                        // Recalculate totals after deleting
                        $quote = $livewire->getOwnerRecord();
                        app(QuoteCalculationService::class)->calculateTotals($quote);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Verwijderen')
                        ->after(function (RelationManager $livewire) {
                            // Recalculate totals after bulk delete
                            $quote = $livewire->getOwnerRecord();
                            app(QuoteCalculationService::class)->calculateTotals($quote);
                        }),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
