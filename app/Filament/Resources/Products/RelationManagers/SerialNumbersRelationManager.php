<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SerialNumbersRelationManager extends RelationManager
{
    protected static string $relationship = 'serialNumbers';

    protected static ?string $title = 'Serienummers';

    protected static ?string $modelLabel = 'serienummer';

    protected static ?string $pluralModelLabel = 'serienummers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('serial_number')
                ->label('Serienummer')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                    return $rule->where('product_id', $this->getOwnerRecord()->id);
                }),
            Forms\Components\TextInput::make('quantity')
                ->label('Totaal aantal')
                ->numeric()
                ->required()
                ->minValue(fn ($record) => $record?->quantity_reserved ?? 0)
                ->helperText(fn ($record) => $record?->quantity_reserved > 0 
                    ? "Minimaal {$record->quantity_reserved} (gereserveerd)" 
                    : null),
            Forms\Components\Textarea::make('notes')
                ->label('Notities')
                ->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serienummer')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Totaal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_reserved')
                    ->label('Gereserveerd')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Beschikbaar')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Toegevoegd op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notities')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('addStock')
                    ->label('Nalevering')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('add_quantity')
                            ->label('Aantal toevoegen')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reden')
                            ->rows(2)
                            ->placeholder('Bijv. nalevering van leverancier'),
                    ])
                    ->action(function (array $data, $record): void {
                        $addQty = (int) $data['add_quantity'];
                        $record->increment('quantity', $addQty);

                        // Also update product stock
                        $product = $record->product;
                        $product->addStock(
                            quantity: $addQty,
                            reason: ($data['reason'] ?? 'Nalevering') . " (serienummer: {$record->serial_number})",
                        );

                        Notification::make()
                            ->success()
                            ->title('Voorraad bijgewerkt')
                            ->body("{$addQty} stuks toegevoegd aan {$record->serial_number}. Nieuw totaal: {$record->fresh()->quantity}")
                            ->send();
                    }),
                EditAction::make()
                    ->label('Bewerken'),
                DeleteAction::make()
                    ->label('Verwijderen')
                    ->visible(fn ($record) => $record->quantity_reserved === 0)
                    ->before(function ($record) {
                        // Reduce product stock when deleting serial number
                        $product = $record->product;
                        if ($record->quantity > 0) {
                            $product->creditStock(
                                quantity: $record->quantity,
                                reason: "Serienummer {$record->serial_number} verwijderd",
                            );
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
