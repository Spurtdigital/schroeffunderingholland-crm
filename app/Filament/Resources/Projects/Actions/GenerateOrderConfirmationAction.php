<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Project;
use Filament\Actions\Action;

class GenerateOrderConfirmationAction
{
    public static function make(?string $name = 'generateOrderConfirmation'): Action
    {
        return Action::make($name)
            ->label('Opdrachtbevestiging PDF')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('info')
            ->url(fn (Project $record) => route('projects.order-confirmation-pdf', $record))
            ->openUrlInNewTab();
    }
}
