<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StockReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockReservations';

    protected static ?string $title = 'Reserveringen';

    protected static ?string $modelLabel = 'reservering';

    protected static ?string $pluralModelLabel = 'reserveringen';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('quote.quote_number')
                    ->label('Offerte')
                    ->searchable()
                    ->url(fn ($record) => $record->quote
                        ? route('filament.admin.resources.quotes.edit', $record->quote)
                        : null
                    ),
                Tables\Columns\TextColumn::make('quote.company.name')
                    ->label('Bedrijf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Aantal')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
