<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Project;
use Filament\Actions\Action;

class PrintWorkTicketAction
{
    public static function make(?string $name = 'printWorkTicket'): Action
    {
        return Action::make($name)
            ->label('Werkbon afdrukken')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(fn (Project $record) => route('projects.work-ticket-pdf', [$record, 'stream' => 1]))
            ->openUrlInNewTab();
    }
}
