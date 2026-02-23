<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTermResource\Pages;
use App\Models\PaymentTerm;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentTermResource extends Resource
{
    protected static ?string $model = PaymentTerm::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?string $modelLabel = 'betalingsvoorwaarde';

    protected static ?string $pluralModelLabel = 'Betalingsvoorwaarden';

    protected static ?string $navigationLabel = 'Betalingsvoorwaarden';

    protected static ?int $navigationSort = 96;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Betalingsvoorwaarde')
                    ->schema([
                        TextInput::make('title')
                            ->label('Naam')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Korte naam voor in de selector, bijv. "100% vooraf"')
                            ->columnSpan(2),

                        TextInput::make('sort_order')
                            ->label('Volgorde')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),

                        Toggle::make('active')
                            ->label('Actief')
                            ->default(true)
                            ->columnSpan(1),

                        Toggle::make('is_default')
                            ->label('Standaard selectie')
                            ->helperText('Wordt automatisch geselecteerd bij een nieuwe offerte')
                            ->default(false)
                            ->columnSpan(2),

                        Textarea::make('content')
                            ->label('Betalingsvoorwaarde tekst')
                            ->required()
                            ->rows(4)
                            ->helperText('Volledige tekst die in de sluitingstekst van de offerte verschijnt')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->label('Tekst')
                    ->limit(80)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Standaard')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Alleen actieve')
                    ->falseLabel('Alleen inactieve'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTerms::route('/'),
            'create' => Pages\CreatePaymentTerm::route('/create'),
            'edit' => Pages\EditPaymentTerm::route('/{record}/edit'),
        ];
    }
}
