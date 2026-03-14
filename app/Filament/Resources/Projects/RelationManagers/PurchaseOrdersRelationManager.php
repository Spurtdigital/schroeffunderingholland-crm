<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $title = 'Inkooporders';

    protected static ?string $modelLabel = 'inkooporder';

    protected static ?string $pluralModelLabel = 'inkooporders';

    public function form(Schema $schema): Schema
    {
        // Creating/editing purchase orders is done via the dedicated resource.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Ordernummer')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => PurchaseOrderResource::getUrl('view', ['record' => $record]))
                    ->color('primary'),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Leverancier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Totaal')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Besteldatum')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Verwachte levering')
                    ->date('d-m-Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(PurchaseOrderStatus::class),
            ])
            ->headerActions([
                // No inline create — use the header action button on the project page instead.
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Openen')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => PurchaseOrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('order_date', 'desc')
            ->emptyStateHeading('Geen inkooporders')
            ->emptyStateDescription('Gebruik de knop "Inkooporder" bovenaan de pagina om een nieuwe inkooporder aan te maken.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}
