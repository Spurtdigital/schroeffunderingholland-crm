<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    protected static ?string $title = 'Projecten';

    protected static ?string $modelLabel = 'project';

    protected static ?string $pluralModelLabel = 'projecten';

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
            ->recordTitleAttribute('project_number')
            ->recordUrl(fn ($record) => ProjectResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('project_number')
                    ->label('Projectnummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quote.quote_number')
                    ->label('Offerte')
                    ->formatStateUsing(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'Concept' : $state)
                    ->color(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'gray' : null)
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoicing_type')
                    ->label('Facturatiemethode')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Montagedatum')
                    ->date('d-m-Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_date')
                    ->label('Opleverdatum')
                    ->date('d-m-Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Bekijken')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => ProjectResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
