<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Project;
use Filament\Actions\Action;

class PrintPackingSlipAction
{
    public static function make(?string $name = 'printPackingSlip'): Action
    {
        return Action::make($name)
            ->label('Pakbon afdrukken')
            ->icon('heroicon-o-printer')
            ->color('info')
            ->url(fn (Project $record) => route('projects.packing-slip-pdf', [$record, 'stream' => 1]))
            ->openUrlInNewTab();
    }
}
