<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Actions\CreatePurchaseOrderAction;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Productnaam')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->short_description),
                TextColumn::make('suppliers.name')
                    ->label('Leverancier(s)')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->separator(', ')
                    ->placeholder('—'),
                TextColumn::make('preferred_supplier_sku')
                    ->label('Art.nr. leverancier')
                    ->getStateUsing(function ($record) {
                        $preferred = $record->suppliers()
                            ->wherePivot('is_preferred', true)
                            ->first();

                        return $preferred?->pivot->supplier_sku
                            ?? $record->suppliers()->first()?->pivot->supplier_sku;
                    })
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('weight')
                    ->label('Gewicht')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 3, ',', '').' kg' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_price')
                    ->label('Inkoopprijs')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sales_price')
                    ->label('Verkoopprijs')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('Voorraad')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->isOutOfStock() ? 'danger' : ($record->isLowStock() ? 'warning' : 'success')),
                TextColumn::make('reserved_quantity')
                    ->label('Gereserveerd')
                    ->getStateUsing(fn ($record) => $record->stockReservations()->where('status', 'active')->sum('quantity'))
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('available_stock')
                    ->label('Beschikbaar')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        ($stock = $record->available_stock) <= 0 => 'danger',
                        $stock <= $record->min_stock_level => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Laatst gewijzigd')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('Leverancier')
                    ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereHas('suppliers', fn ($q) => $q->where('supplier_id', $data['value']))
                        : $query
                    )
                    ->placeholder('Alle leveranciers'),
                TernaryFilter::make('is_active')
                    ->label('Actief')
                    ->placeholder('Alle producten')
                    ->trueLabel('Alleen actieve producten')
                    ->falseLabel('Alleen inactieve producten'),
                TernaryFilter::make('low_stock')
                    ->label('Lage voorraad')
                    ->placeholder('Alle producten')
                    ->queries(
                        true: fn ($query) => $query->whereRaw("(stock_quantity - COALESCE((SELECT SUM(quantity) FROM stock_reservations WHERE product_id = products.id AND status = 'active'), 0)) <= min_stock_level"),
                        false: fn ($query) => $query->whereRaw("(stock_quantity - COALESCE((SELECT SUM(quantity) FROM stock_reservations WHERE product_id = products.id AND status = 'active'), 0)) > min_stock_level"),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Bewerken'),
                CreatePurchaseOrderAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Verwijderen'),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
