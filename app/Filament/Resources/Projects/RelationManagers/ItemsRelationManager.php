<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\SerialNumber;
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
                Tables\Columns\TextColumn::make('serialNumbers')
                    ->label('Serienummers')
                    ->badge()
                    ->color(fn ($record) => $this->getSerialBadgeColor($record))
                    ->formatStateUsing(fn ($record) => $this->getSerialBadgeText($record))
                    ->tooltip(fn ($record) => $this->getSerialTooltip($record)),
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
                Action::make('assignSerialNumbers')
                    ->label('Serienummers')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->visible(fn ($record) => $record->type === QuoteItemType::Product
                        && $record->itemable_type === Product::class
                        && $record->itemable_id
                        && (int) $record->quantity > 0
                    )
                    ->modalHeading(fn ($record) => "Serienummers voor {$record->description}")
                    ->modalDescription(fn ($record) => "Wijs serienummers toe voor {$record->quantity} stuks.")
                    ->modalWidth('xl')
                    ->form(function ($record) {
                        $product = Product::find($record->itemable_id);
                        $requiredQuantity = (int) $record->quantity;

                        // Get available serial numbers for this product
                        $availableSerials = SerialNumber::where('product_id', $product->id)
                            ->where(function ($q) {
                                $q->whereRaw('quantity > quantity_reserved');
                            })
                            ->get();

                        // Get already assigned serial numbers for this item
                        $assignedSerials = $record->serialNumbers()->get();

                        $fields = [];

                        // Repeater for assigning serial numbers with quantities
                        $fields[] = Forms\Components\Repeater::make('assignments')
                            ->label('Serienummer toewijzingen')
                            ->schema([
                                Forms\Components\Select::make('serial_number_id')
                                    ->label('Serienummer')
                                    ->options(function () use ($availableSerials, $assignedSerials) {
                                        $options = [];
                                        foreach ($availableSerials as $serial) {
                                            $options[$serial->id] = "{$serial->serial_number} (beschikbaar: {$serial->available_quantity})";
                                        }
                                        // Include already assigned ones
                                        foreach ($assignedSerials as $serial) {
                                            if (!isset($options[$serial->id])) {
                                                $totalAvailable = $serial->available_quantity + $serial->pivot->quantity;
                                                $options[$serial->id] = "{$serial->serial_number} (beschikbaar: {$totalAvailable})";
                                            }
                                        }
                                        return $options;
                                    })
                                    ->required()
                                    ->searchable()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Aantal')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Serienummer toevoegen')
                            ->default(function () use ($assignedSerials) {
                                $defaults = [];
                                foreach ($assignedSerials as $serial) {
                                    $defaults[] = [
                                        'serial_number_id' => $serial->id,
                                        'quantity' => $serial->pivot->quantity,
                                    ];
                                }
                                return $defaults;
                            })
                            ->helperText("Totaal toe te wijzen: {$requiredQuantity} stuks");

                        return $fields;
                    })
                    ->action(function (array $data, $record): void {
                        $assignments = $data['assignments'] ?? [];
                        $requiredQuantity = (int) $record->quantity;

                        // Calculate total assigned
                        $totalAssigned = 0;
                        foreach ($assignments as $assignment) {
                            $totalAssigned += (int) ($assignment['quantity'] ?? 0);
                        }

                        // Warn if not matching, but allow it
                        if ($totalAssigned > $requiredQuantity) {
                            Notification::make()
                                ->danger()
                                ->title('Te veel toegewezen')
                                ->body("Je hebt {$totalAssigned} stuks toegewezen, maar er zijn maar {$requiredQuantity} stuks nodig.")
                                ->send();
                            return;
                        }

                        // Release all current assignments
                        foreach ($record->serialNumbers as $serial) {
                            $serial->release($record);
                        }

                        // Create new assignments
                        foreach ($assignments as $assignment) {
                            $serialNumber = SerialNumber::find($assignment['serial_number_id']);
                            $qty = (int) $assignment['quantity'];

                            if (!$serialNumber) {
                                continue;
                            }

                            if ($qty > $serialNumber->available_quantity) {
                                Notification::make()
                                    ->danger()
                                    ->title('Onvoldoende voorraad')
                                    ->body("Serienummer {$serialNumber->serial_number} heeft maar {$serialNumber->available_quantity} beschikbaar.")
                                    ->send();
                                return;
                            }

                            $serialNumber->reserve($record, $qty);
                        }

                        if ($totalAssigned < $requiredQuantity) {
                            Notification::make()
                                ->warning()
                                ->title('Serienummers toegewezen')
                                ->body("{$totalAssigned} van {$requiredQuantity} stuks gekoppeld. Nog " . ($requiredQuantity - $totalAssigned) . " stuks zonder serienummer.")
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('Serienummers toegewezen')
                                ->body("{$totalAssigned} stuks gekoppeld aan dit item.")
                                ->send();
                        }
                    }),
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

    private function getSerialBadgeColor($record): string
    {
        if ($record->type !== QuoteItemType::Product || $record->itemable_type !== Product::class) {
            return 'gray';
        }

        $assigned = $record->serialNumbers->sum('pivot.quantity');
        $required = (int) $record->quantity;

        if ($assigned === 0) {
            return 'gray';
        }

        return $assigned >= $required ? 'success' : 'warning';
    }

    private function getSerialBadgeText($record): string
    {
        if ($record->type !== QuoteItemType::Product || $record->itemable_type !== Product::class) {
            return '-';
        }

        $assigned = $record->serialNumbers->sum('pivot.quantity');
        $required = (int) $record->quantity;

        return "{$assigned}/{$required}";
    }

    private function getSerialTooltip($record): ?string
    {
        if ($record->type !== QuoteItemType::Product || $record->itemable_type !== Product::class) {
            return null;
        }

        $serials = $record->serialNumbers->map(function ($serial) {
            return "{$serial->serial_number} ({$serial->pivot->quantity}x)";
        })->join(', ');

        return $serials ?: null;
    }
}
