<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Resources\Companies\Actions\SyncToMoneybirdAction;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Klantnaam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mailadres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Telefoonnummer')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('Plaats')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leads_count')
                    ->label('Aantal leads')
                    ->counts('leads')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('quotes_count')
                    ->label('Aantal offertes')
                    ->counts('quotes')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('projects_count')
                    ->label('Aantal projecten')
                    ->counts('projects')
                    ->sortable()
                    ->toggleable(),
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
                //
            ])
            ->recordUrl(fn ($record) => CompanyResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label('Bekijken'),
                EditAction::make()
                    ->label('Bewerken'),
                SyncToMoneybirdAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
