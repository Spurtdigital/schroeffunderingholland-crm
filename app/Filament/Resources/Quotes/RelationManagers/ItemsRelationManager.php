<?php

namespace App\Filament\Resources\Quotes\RelationManagers;

use App\Enums\QuoteItemType;
use App\Models\Machine;
use App\Models\Product;
use App\Services\DistanceCalculatorService;
use App\Services\QuoteCalculationService;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

                Forms\Components\TextInput::make('pile_length')
                    ->label('Diepte fundament (m)')
                    ->numeric()
                    ->nullable()
                    ->minValue(0.1)
                    ->step(0.01)
                    ->suffix('m')
                    ->placeholder('bijv. 3.75')
                    ->visible(fn (Forms\Components\TextInput $component) => $component->getRecord()?->type === \App\Enums\QuoteItemType::Product)
                    ->columnSpan(1),

                Forms\Components\Select::make('machine_id')
                    ->label('Machine')
                    ->options(Machine::where('active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->placeholder('Geen machine')
                    ->visible(fn (Forms\Components\Select $component) => in_array($component->getRecord()?->type, [
                        \App\Enums\QuoteItemType::Product,
                        \App\Enums\QuoteItemType::Machine,
                    ]))
                    ->columnSpan(2),

                Forms\Components\TextInput::make('quantity')
                    ->label('Aantal')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->live()
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                    ->visible(fn (Forms\Components\TextInput $component) => $component->getRecord()?->type !== \App\Enums\QuoteItemType::Machine)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Prijs per stuk (excl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->step(0.01)
                    ->live()
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('quantity') ?? 1), 2)))
                    ->visible(fn (Forms\Components\TextInput $component) => $component->getRecord()?->type !== \App\Enums\QuoteItemType::Machine)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('total')
                    ->label('Totaal (excl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn (Forms\Components\TextInput $component) => $component->getRecord()?->type !== \App\Enums\QuoteItemType::Machine)
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
                Tables\Columns\TextColumn::make('pile_length')
                    ->label('Diepte')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '') . ' m' : '—')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Aantal')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Prijs')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Totaal')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('machine.name')
                    ->label('Machine')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(QuoteItemType::class),
            ])
            ->headerActions([
                ActionGroup::make([
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

                // Add Labor
                Action::make('addLabor')
                    ->label('Arbeid toevoegen')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('description')
                            ->label('Omschrijving')
                            ->default('Arbeid')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Aantal uren')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(0.25)
                            ->step(0.25)
                            ->suffix('uur')
                            ->live()
                            ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total', round($state * ($get('unit_price') ?? 0), 2)))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Uurtarief (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0.01)
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
                        $data['type'] = QuoteItemType::Labor->value;
                        $data['itemable_type'] = null;
                        $data['itemable_id'] = null;

                        $quote = $livewire->getOwnerRecord();
                        $quote->items()->create($data);

                        app(QuoteCalculationService::class)->calculateTotals($quote);

                        Notification::make()
                            ->success()
                            ->title('Arbeid toegevoegd')
                            ->send();
                    }),

                // Add Machine
                Action::make('addMachine')
                    ->label('Machine toevoegen')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('machine_id')
                            ->label('Machine')
                            ->options(Machine::where('active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $machine = Machine::find($state);
                                    if ($machine) {
                                        $set('description', $machine->name);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('description')
                            ->label('Omschrijving')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $quote = $livewire->getOwnerRecord();
                        $quote->items()->create([
                            'type'          => QuoteItemType::Machine->value,
                            'machine_id'    => $data['machine_id'],
                            'description'   => $data['description'],
                            'quantity'      => 1,
                            'unit_price'    => 0,
                            'total'         => 0,
                            'itemable_type' => null,
                            'itemable_id'   => null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Machine toegevoegd')
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

                // Add Mileage (automatically calculated)
                Action::make('addMileage')
                    ->label('Reiskosten toevoegen')
                    ->icon('heroicon-o-map-pin')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Reiskosten automatisch berekenen')
                    ->modalDescription('De afstand wordt automatisch berekend via Google Maps. Als het werkadres afwijkt van het factuuradres wordt het werkadres gebruikt. De reiskosten worden berekend voor heen én terug.')
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

                        if (! $quote->company) {
                            Notification::make()
                                ->warning()
                                ->title('Geen bedrijf gevonden')
                                ->body('Deze offerte heeft geen bedrijf gekoppeld.')
                                ->send();

                            return;
                        }

                        $address = $distanceService->buildAddressFromQuote($quote);
                        if (! $address) {
                            Notification::make()
                                ->warning()
                                ->title('Geen adres beschikbaar')
                                ->body('Het bedrijf heeft geen volledig adres en er is geen werkadres ingevuld.')
                                ->send();

                            return;
                        }

                        // Calculate distance (one way)
                        $result = $distanceService->calculateDistance($address);

                        if ($result['success']) {
                            $pricePerKm = $settings->default_travel_cost_per_km ?? 0.23;
                            $totalKm = $result['distance_km'] * 2; // heen én terug
                            $total = round($totalKm * $pricePerKm, 2);

                            $originAddress = trim($settings->company_address ?? '');
                            $destAddress   = trim($distanceService->buildAddressFromQuote($quote) ?? '');
                            $travelDesc    = "Reiskosten: {$originAddress} → {$destAddress} en retour ({$result['distance_text']} × 2 = {$totalKm} km)";

                            // Create the quote item
                            $quote->items()->create([
                                'type' => QuoteItemType::Mileage->value,
                                'description' => $travelDesc,
                                'quantity' => $totalKm,
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
                                ->body("{$result['distance_text']} heen × 2 = {$totalKm} km × €{$pricePerKm} = €{$total} (ca. {$result['duration_text']} enkele reis)")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Afstand berekenen mislukt')
                                ->body($result['error'] ?? 'Kon de afstand niet berekenen.')
                                ->send();
                        }
                    }),

                // Add Discount
                Action::make('addDiscount')
                    ->label('Korting toevoegen')
                    ->icon('heroicon-o-tag')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('description')
                            ->label('Omschrijving')
                            ->default('Korting')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Kortingsbedrag (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->helperText('Voer een positief bedrag in — wordt automatisch als aftrekpost opgeslagen.')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $amount = abs((float) $data['discount_amount']);

                        $quote = $livewire->getOwnerRecord();
                        $quote->items()->create([
                            'type'         => QuoteItemType::Discount->value,
                            'description'  => $data['description'],
                            'quantity'     => 1,
                            'unit_price'   => -$amount,
                            'total'        => -$amount,
                            'itemable_type' => null,
                            'itemable_id'   => null,
                        ]);

                        app(QuoteCalculationService::class)->calculateTotals($quote);

                        Notification::make()
                            ->success()
                            ->title('Korting toegevoegd')
                            ->body('€ ' . number_format($amount, 2, ',', '.') . ' korting is toegevoegd.')
                            ->send();
                    }),
                ])
                    ->label('Regel toevoegen')
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->color('primary'),
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
