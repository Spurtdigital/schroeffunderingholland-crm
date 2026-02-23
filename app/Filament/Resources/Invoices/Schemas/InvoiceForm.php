<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('invoice_number')
                    ->label('Factuurnummer')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(2),
                    
                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'project_number')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),
                    
                Select::make('company_id')
                    ->label('Bedrijf')
                    ->relationship('company', 'name', fn ($query) => $query->orderBy('name'))
                    ->required()
                    ->searchable()
                    ->columnSpan(1),
                    
                Select::make('invoice_type')
                    ->label('Type')
                    ->options(InvoiceType::class)
                    ->required()
                    ->default(InvoiceType::Single)
                    ->columnSpan(1),
                    
                Select::make('status')
                    ->label('Status')
                    ->options(InvoiceStatus::class)
                    ->default(InvoiceStatus::Draft)
                    ->required()
                    ->columnSpan(1),
                    
                DatePicker::make('invoice_date')
                    ->label('Factuurdatum')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d-m-Y')
                    ->columnSpan(1),
                    
                DatePicker::make('due_date')
                    ->label('Vervaldatum')
                    ->required()
                    ->default(now()->addDays(14))
                    ->native(false)
                    ->displayFormat('d-m-Y')
                    ->columnSpan(1),
                    
                TextInput::make('subtotal')
                    ->label('Bedrag excl. BTW')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->columnSpan(1),
                    
                TextInput::make('vat_amount')
                    ->label('BTW bedrag')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->columnSpan(1),
                    
                TextInput::make('total')
                    ->label('Totaal incl. BTW')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->columnSpan(2),
                    
                TextInput::make('moneybird_invoice_id')
                    ->label('Moneybird ID')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(2),
                    
                Textarea::make('notes')
                    ->label('Notities')
                    ->rows(3)
                    ->columnSpan(2),
            ]);
    }
}
