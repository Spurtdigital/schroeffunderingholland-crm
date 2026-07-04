<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockReservationStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StockReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockReservations';

    protected static ?string $title = 'Reserveringen';

    protected static ?string $modelLabel = 'reservering';

    protected static ?string $pluralModelLabel = 'reserveringen';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('project.project_number')
                    ->label('Project')
                    ->searchable()
                    ->url(fn ($record) => $record->project
                        ? route('filament.admin.resources.projects.view', $record->project)
                        : null
                    )
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('quote.quote_number')
                    ->label('Offerte')
                    ->formatStateUsing(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'Concept' : $state)
                    ->color(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'gray' : null)
                    ->searchable()
                    ->url(fn ($record) => $record->quote
                        ? route('filament.admin.resources.quotes.edit', $record->quote)
                        : null
                    )
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('project.company.name')
                    ->label('Bedrijf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Aantal')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->actions([
                Action::make('release')
                    ->label('Verwijderen')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reservering verwijderen')
                    ->modalDescription(fn ($record) => "Weet je zeker dat je de reservering van {$record->quantity}x" . ($record->project ? " voor project {$record->project->project_number}" : ($record->quote ? " voor offerte {$record->quote->quote_number}" : '')) . ' wilt verwijderen? De voorraad komt weer beschikbaar.')
                    ->modalSubmitActionLabel('Verwijderen')
                    ->visible(fn ($record) => $record->status === StockReservationStatus::Active)
                    ->action(function ($record) {
                        $record->release();

                        Notification::make()
                            ->title('Reservering verwijderd')
                            ->body("De reservering is vrijgegeven en de voorraad is weer beschikbaar.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}

