<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Filament\Resources\Projects\Actions\GeneratePackingSlipAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_number')
                    ->label('Projectnummer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Bedrijf')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quote.quote_number')
                    ->label('Offerte')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('invoicing_type')
                    ->label('Facturatiemethode')
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Startdatum')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('completion_date')
                    ->label('Verwachte oplevering')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Gewijzigd op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Bekijken'),
                EditAction::make()
                    ->label('Bewerken'),
                GeneratePackingSlipAction::make(),
            ])
            ->recordUrl(fn ($record) => route('filament.admin.resources.projects.view', ['record' => $record]))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Verwijderen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
