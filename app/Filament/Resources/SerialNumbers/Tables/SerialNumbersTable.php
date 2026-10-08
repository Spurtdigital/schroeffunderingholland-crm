<?php

namespace App\Filament\Resources\SerialNumbers\Tables;

use App\Models\SerialNumber;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SerialNumbersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['product', 'projectItems.project.company']))
            ->columns([
                TextColumn::make('serial_number')
                    ->label('Serienummer / Batch')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Totaal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quantity_reserved')
                    ->label('Gereserveerd')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('available_quantity')
                    ->label('Beschikbaar')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('deliveries')
                    ->label('Geleverd aan')
                    ->getStateUsing(fn (SerialNumber $record): array => collect($record->deliveryLocations())
                        ->map(fn ($location) => trim(
                            ($location['project_number'] ?? '—').' · '.($location['company'] ?? '—').' ('.$location['quantity'].'x)'
                        ))
                        ->all()
                    )
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder('Niet geleverd'),
                TextColumn::make('created_at')
                    ->label('Toegevoegd op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('deliveries')
                    ->label('Leveringen')
                    ->icon('heroicon-o-map-pin')
                    ->color('gray')
                    ->visible(fn (SerialNumber $record) => $record->projectItems()->exists())
                    ->modalHeading(fn (SerialNumber $record) => 'Leveringen van '.$record->serial_number)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Sluiten')
                    ->modalContent(fn (SerialNumber $record) => view('filament.serial-number-deliveries', [
                        'serial' => $record->load('projectItems.project.company'),
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
