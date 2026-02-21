<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Filament\Resources\Invoices\Actions\GeneratePdfAction;
use App\Filament\Resources\Invoices\Actions\SendToMoneybirdAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Factuurnummer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.project_number')
                    ->label('Project')
                    ->searchable(),
                TextColumn::make('project.company.name')
                    ->label('Klant')
                    ->searchable(),
                TextColumn::make('invoice_type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Bedrag')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('moneybird_invoice_id')
                    ->label('Moneybird ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('prepared_at')
                    ->label('Voorbereid op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label('Verstuurd op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Betaald op')
                    ->dateTime('d-m-Y H:i')
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
                GeneratePdfAction::make(),
                SendToMoneybirdAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
