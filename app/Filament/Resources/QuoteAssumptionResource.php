<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteAssumptionResource\Pages;
use App\Models\QuoteAssumption;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuoteAssumptionResource extends Resource
{
    protected static ?string $model = QuoteAssumption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?string $modelLabel = 'offerte-aanname';

    protected static ?string $pluralModelLabel = 'Offerte-aannames';

    protected static ?string $navigationLabel = 'Offerte-aannames';

    protected static ?int $navigationSort = 95;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aanname')
                    ->schema([
                        TextInput::make('title')
                            ->label('Beheernaam')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Interne naam voor in de checkboxlijst, bijv. "Verlengbaar fundament 3.5m"')
                            ->columnSpan(3),

                        Select::make('category')
                            ->label('Categorie')
                            ->options([
                                'Grondopbouw' => 'Grondopbouw',
                                'Bouwwerk' => 'Bouwwerk',
                                'Machines' => 'Machines',
                            ])
                            ->nullable()
                            ->placeholder('Geen categorie')
                            ->helperText('Aannames worden per categorie gegroepeerd in Moneybird.')
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
                            ->columnSpan(2),

                        TextInput::make('trigger_sku')
                            ->label('Auto-aanvinken bij SKU')
                            ->maxLength(100)
                            ->nullable()
                            ->helperText('Als een offerteregel een product heeft waarvan de SKU dit woord/fragment bevat, wordt deze aanname automatisch aangevinkt. Leeg = nooit auto-aangevinkt.')
                            ->placeholder('bijv. VERLENGBAAR')
                            ->columnSpan(4),

                        Textarea::make('content')
                            ->label('Bullet-tekst (in offerte)')
                            ->required()
                            ->rows(3)
                            ->helperText('Tekst die als bullet verschijnt. Gebruik {value} als placeholder voor variabele invoer.')
                            ->columnSpanFull(),
                    ])
                    ->columns(6),

                Section::make('Variabele invoer')
                    ->description('Stel in als deze aanname een variabele waarde heeft die per offerte ingevuld wordt.')
                    ->schema([
                        Toggle::make('has_variable_input')
                            ->label('Heeft variabele invoer')
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('variable_input_label')
                            ->label('Label invoerveld')
                            ->placeholder('bijv. Diepte')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('has_variable_input'))
                            ->columnSpan(1),

                        TextInput::make('variable_input_suffix')
                            ->label('Suffix / eenheid')
                            ->placeholder('bijv. meter')
                            ->maxLength(50)
                            ->visible(fn (Get $get) => $get('has_variable_input'))
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record) => ! $record?->has_variable_input),
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
                    ->label('Beheernaam')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categorie')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Grondopbouw' => 'success',
                        'Bouwwerk' => 'info',
                        'Machines' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Tekst')
                    ->limit(80)
                    ->searchable(),

                Tables\Columns\TextColumn::make('trigger_sku')
                    ->label('SKU-trigger')
                    ->placeholder('-')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_variable_input')
                    ->label('Variabel')
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
            'index' => Pages\ListQuoteAssumptions::route('/'),
            'create' => Pages\CreateQuoteAssumption::route('/create'),
            'edit' => Pages\EditQuoteAssumption::route('/{record}/edit'),
        ];
    }
}
