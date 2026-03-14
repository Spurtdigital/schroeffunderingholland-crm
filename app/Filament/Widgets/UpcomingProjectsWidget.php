<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingProjectsWidget extends BaseWidget
{
    protected static ?string $heading = 'Komende projecten (7 dagen)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Project::query()
            ->whereIn('status', [ProjectStatus::Active])
            ->where(function (Builder $query) {
                $query->whereBetween('start_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                    ->orWhereBetween('completion_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()]);
            })
            ->orderBy('start_date')
            ->orderBy('completion_date');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('project_number')
                    ->label('Projectnummer')
                    ->weight('bold')
                    ->url(fn (Project $record) => route('filament.admin.resources.projects.view', ['record' => $record]))
                    ->openUrlInNewTab(false),

                TextColumn::make('company.name')
                    ->label('Klant')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('start_date')
                    ->label('Montagedatum')
                    ->date('d-m-Y')
                    ->placeholder('-')
                    ->color(fn (?string $state) => $state && now()->isSameDay($state) ? 'warning' : null),

                TextColumn::make('completion_date')
                    ->label('Opleverdatum')
                    ->date('d-m-Y')
                    ->placeholder('-')
                    ->color(fn (Project $record) => $record->completion_date?->isToday() ? 'warning' : null),

                TextColumn::make('project_value')
                    ->label('Projectwaarde')
                    ->money('EUR')
                    ->placeholder('-'),
            ])
            ->emptyStateHeading('Geen komende projecten')
            ->emptyStateDescription('Er zijn geen actieve projecten in de komende 7 dagen.')
            ->emptyStateIcon('heroicon-o-calendar')
            ->paginated(false);
    }
}
