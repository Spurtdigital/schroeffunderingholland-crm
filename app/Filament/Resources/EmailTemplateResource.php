<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?string $modelLabel = 'email template';

    protected static ?string $pluralModelLabel = 'Email Templates';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Informatie')
                    ->schema([
                        TextInput::make('name')
                            ->label('Naam')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('key')
                            ->label('Sleutel (uniek)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->disabled(fn ($record) => $record !== null)
                            ->helperText('Deze sleutel wordt gebruikt in de code. Niet aanpassen na aanmaken!')
                            ->columnSpan(1),

                        Toggle::make('active')
                            ->label('Actief')
                            ->default(true)
                            ->columnSpan(1),

                        Textarea::make('description')
                            ->label('Beschrijving')
                            ->rows(2)
                            ->helperText('Wanneer wordt deze template gebruikt?')
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Section::make('Email Inhoud')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Onderwerp')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Je kunt variabelen gebruiken zoals {{klant_naam}}')
                            ->columnSpanFull(),

                        Textarea::make('content')
                            ->label('Inhoud')
                            ->required()
                            ->rows(15)
                            ->helperText('Gebruik variabelen zoals {{klant_naam}}, {{bedrag}}, etc.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Beschikbare Variabelen')
                    ->schema([
                        Placeholder::make('variables_help')
                            ->label('Beschikbare variabelen')
                            ->content(fn ($record) => $record?->available_variables_text ?? 'Sla eerst op om variabelen te zien')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Sleutel')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Onderwerp')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\IconColumn::make('active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Laatst gewijzigd')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Alle templates')
                    ->trueLabel('Alleen actieve')
                    ->falseLabel('Alleen inactieve'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\Action::make('preview')
                    ->label('Voorbeeld')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Voorbeeld: '.$record->name)
                    ->modalContent(fn ($record) => view('filament.email-template-preview', ['template' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Sluiten'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
