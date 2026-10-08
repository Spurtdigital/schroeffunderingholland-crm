<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Facturen';

    protected static ?string $modelLabel = 'factuur';

    protected static ?string $pluralModelLabel = 'facturen';

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
            ->recordTitleAttribute('invoice_number')
            ->recordUrl(fn ($record) => InvoiceResource::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Factuurnummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.project_number')
                    ->label('Project')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Bedrag')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Factuurdatum')
                    ->date('d-m-Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vervaldatum')
                    ->date('d-m-Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Verstuurd op')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Betaald op')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Bekijken')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => InvoiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('invoice_date', 'desc');
    }
}
