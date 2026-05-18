<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Project;
use Filament\Actions\Action;

class GenerateWorkTicketAction
{
    public static function make(?string $name = 'generateWorkTicket'): Action
    {
        return Action::make($name)
            ->label('Werkbon PDF')
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->url(fn (Project $record) => route('projects.work-ticket-pdf', $record))
            ->openUrlInNewTab();
    }
}
