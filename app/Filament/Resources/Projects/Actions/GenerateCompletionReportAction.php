<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Project;
use Filament\Actions\Action;

class GenerateCompletionReportAction
{
    public static function make(?string $name = 'generateCompletionReport'): Action
    {
        return Action::make($name)
            ->label('Opleverrapport PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->visible(fn (Project $record) => $record->isCompleted())
            ->url(fn (Project $record) => route('projects.completion-report-pdf', $record))
            ->openUrlInNewTab();
    }
}
