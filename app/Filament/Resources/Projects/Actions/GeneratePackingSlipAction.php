<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Project;
use Filament\Actions\Action;

class GeneratePackingSlipAction
{
    public static function make(?string $name = 'generatePackingSlip'): Action
    {
        return Action::make($name)
            ->label('Pakbon PDF')
            ->icon('heroicon-o-document-text')
            ->color('info')
            ->url(fn (Project $record) => route('projects.packing-slip-pdf', $record))
            ->openUrlInNewTab();
    }
}
