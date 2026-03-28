<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Enums\QuoteStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';
    
    protected static ?string $title = 'Offertes';
    
    protected static ?string $modelLabel = 'offerte';
    
    protected static ?string $pluralModelLabel = 'offertes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('quote_number')
                    ->label('Offertenummer')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-')
                        ? 'Concept (nog niet verstuurd naar Moneybird)'
                        : $state
                    ),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(QuoteStatus::class)
                    ->default(QuoteStatus::Draft)
                    ->required(),
                Forms\Components\DatePicker::make('valid_until')
                    ->label('Geldig tot')
                    ->required()
                    ->default(now()->addDays(30))
                    ->native(false)
                    ->displayFormat('d-m-Y'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notities')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('quote_number')
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')
                    ->label('Nummer')
                    ->formatStateUsing(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'Concept' : $state)
                    ->color(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'gray' : null)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_excl_vat')
                    ->label('Totaal excl. BTW')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Geldig tot')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(QuoteStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nieuwe offerte'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Bewerken'),
                DeleteAction::make()
                    ->label('Verwijderen'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Verwijderen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
