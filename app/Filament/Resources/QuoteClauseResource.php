<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteClauseResource\Pages;
use App\Models\QuoteClause;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuoteClauseResource extends Resource
{
    protected static ?string $model = QuoteClause::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?string $modelLabel = 'clausule';

    protected static ?string $pluralModelLabel = 'Offerte Clausules';

    protected static ?string $navigationLabel = 'Offerte Clausules';

    protected static ?int $navigationSort = 95;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Clausule')
                    ->description('Een clausule is een conditioneel tekstblok in de sluitingstekst van de offerte. Per offerte kun je clausules aan- of uitvinken.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Naam')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Korte naam voor intern gebruik, bijv. "Stroom 230V vereist"')
                            ->columnSpan(2),

                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'bullet' => 'Bullet (voorwaarden-lijst)',
                                'section' => 'Sectie (vrije alinea met kopje)',
                            ])
                            ->default('bullet')
                            ->required()
                            ->helperText('Bullet = punt in de voorwaarden-lijst. Sectie = eigen kopje + alinea.')
                            ->columnSpan(2),

                        TextInput::make('sort_order')
                            ->label('Volgorde')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Lagere nummers verschijnen eerst')
                            ->columnSpan(1),

                        Toggle::make('active')
                            ->label('Actief')
                            ->default(true)
                            ->helperText('Inactieve clausules zijn niet selecteerbaar bij nieuwe offertes')
                            ->columnSpan(1),

                        Textarea::make('content')
                            ->label('Clausuletekst')
                            ->required()
                            ->rows(6)
                            ->helperText('De volledige tekst die in de sluitingstekst van de offerte verschijnt')
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

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state) => $state === 'section' ? 'Sectie' : 'Bullet')
                    ->color(fn (string $state) => $state === 'section' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Tekst')
                    ->limit(80)
                    ->searchable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Gewijzigd')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Alle clausules')
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuoteClauses::route('/'),
            'create' => Pages\CreateQuoteClause::route('/create'),
            'edit' => Pages\EditQuoteClause::route('/{record}/edit'),
        ];
    }
}
