<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
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

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';
    
    protected static ?string $title = 'Facturen';
    
    protected static ?string $modelLabel = 'factuur';
    
    protected static ?string $pluralModelLabel = 'facturen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('Factuurnummer')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(2),
                    
                Forms\Components\Select::make('invoice_type')
                    ->label('Type')
                    ->options(InvoiceType::class)
                    ->required()
                    ->default(InvoiceType::Final)
                    ->columnSpan(1),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(InvoiceStatus::class)
                    ->default(InvoiceStatus::Draft)
                    ->required()
                    ->columnSpan(1),
                    
                Forms\Components\TextInput::make('subtotal')
                    ->label('Bedrag excl. BTW')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->columnSpan(1),
                    
                Forms\Components\TextInput::make('vat_amount')
                    ->label('BTW bedrag')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->columnSpan(1),
                    
                Forms\Components\TextInput::make('total')
                    ->label('Totaal incl. BTW')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->columnSpan(2),
                    
                Forms\Components\DatePicker::make('invoice_date')
                    ->label('Factuurdatum')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d-m-Y')
                    ->columnSpan(1),
                    
                Forms\Components\DatePicker::make('due_date')
                    ->label('Vervaldatum')
                    ->required()
                    ->default(now()->addDays(14))
                    ->native(false)
                    ->displayFormat('d-m-Y')
                    ->columnSpan(1),
                    
                Forms\Components\TextInput::make('moneybird_invoice_id')
                    ->label('Moneybird ID')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(2),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Notities')
                    ->rows(3)
                    ->columnSpan(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nummer')
                    ->searchable()
                    ->sortable(),
                    
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
                    ->label('Datum')
                    ->date('d-m-Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vervaldatum')
                    ->date('d-m-Y')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('moneybird_invoice_id')
                    ->label('Moneybird')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('invoice_type')
                    ->label('Type')
                    ->options(InvoiceType::class),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(InvoiceStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nieuwe factuur'),
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
            ->defaultSort('invoice_date', 'desc');
    }
}
