<?php

namespace App\Filament\Resources\Quotes\Tables;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Actions\ApproveQuoteAction;
use App\Filament\Resources\Quotes\Actions\ConvertToProjectAction;
use App\Filament\Resources\Quotes\Actions\DuplicateQuoteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->label('Offertenummer')
                    ->formatStateUsing(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'Concept' : $state)
                    ->color(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'gray' : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Klant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Totaal excl. BTW')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Totaal incl. BTW')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Geldig tot')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Verstuurd op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('approved_at')
                    ->label('Goedgekeurd op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(QuoteStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->label('Bewerken'),
                DuplicateQuoteAction::make(),
                ApproveQuoteAction::make(),
                ConvertToProjectAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
