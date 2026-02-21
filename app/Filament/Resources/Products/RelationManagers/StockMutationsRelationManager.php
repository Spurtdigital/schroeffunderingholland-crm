<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StockMutationsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMutations';

    protected static ?string $title = 'Voorraadhistorie';

    protected static ?string $modelLabel = 'mutatie';

    protected static ?string $pluralModelLabel = 'mutaties';

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
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Aantal')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').$state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('quantity_before')
                    ->label('Voor')
                    ->numeric(),
                Tables\Columns\TextColumn::make('quantity_after')
                    ->label('Na')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reden')
                    ->placeholder('—')
                    ->limit(50),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Gebruiker')
                    ->placeholder('Systeem'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
